<?php

namespace MyAds\Plugins\AiMember;

use App\Models\Option;
use App\Models\User;
use App\Models\Status;
use App\Models\Message;
use App\Models\ForumComment;
use App\Models\ForumTopic;
use App\Models\Like;
use App\Models\ForumAttachment;
use App\Models\StatusMention;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\UserPrivacySetting;
use App\Services\NotificationService;
use App\Services\MentionService;
use App\Services\UserBlockService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiMemberService
{
    private string $model = 'gemini-flash-latest';
    private string $botUserIdOption = 'ai_member_bot_uid';
    private string $configOption = 'ai_member_config';
    private string $activityLogsOption = 'ai_member_activity_logs';
    private string $ignoredTasksOption = 'ai_member_ignored_tasks';

    /**
     * Retrieve the stored configuration array.
     */
    public function getConfig(): array
    {
        $option = Option::where('name', $this->configOption)->first();
        return $option ? (json_decode($option->o_valuer, true) ?: []) : [];
    }

    /**
     * Persist the configuration array, normalizing checkbox values.
     */
    public function saveConfig(array $data): void
    {
        $checkboxes = [
            'is_enabled',
            'enable_messages',
            'enable_comments',
            'enable_reactions',
            'enable_auto_block',
            'enable_image_posts',
            'enable_group_moderation',
        ];

        foreach ($checkboxes as $cb) {
            $data[$cb] = !empty($data[$cb]) ? 1 : 0;
        }

        Option::updateOrCreate(
            ['name' => $this->configOption],
            [
                'o_valuer' => json_encode($data),
                'o_type' => 'ai_member',
            ]
        );
    }

    /**
     * Check whether the AI Member is globally enabled with a configured API key.
     */
    public function isEnabled(): bool
    {
        $config = $this->getConfig();
        return !empty($config['is_enabled']) && !empty($config['api_key']);
    }

    /**
     * Fetch list of available text-generation models from Google Gemini API.
     */
    public function listAvailableModels(?string $apiKey = null): array
    {
        if (!$apiKey) {
            $config = $this->getConfig();
            $apiKey = $config['api_key'] ?? null;
        }

        if (!$apiKey) {
            return ['success' => false, 'error' => 'API key is required.'];
        }

        try {
            $response = Http::timeout(15)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Google API returned HTTP ' . $response->status() . ': ' . $response->body(),
                ];
            }

            $data = $response->json();
            $models = [];
            if (isset($data['models']) && is_array($data['models'])) {
                foreach ($data['models'] as $model) {
                    $name = $model['name'] ?? '';
                    $name = str_replace('models/', '', $name);
                    $methods = $model['supportedGenerationMethods'] ?? [];

                    if (!in_array('generateContent', $methods, true)) {
                        continue;
                    }

                    if (
                        stripos($name, 'embedding') !== false ||
                        stripos($name, 'aqa') !== false ||
                        stripos($name, 'imagen') !== false ||
                        stripos($name, 'veo') !== false
                    ) {
                        continue;
                    }

                    $displayName = $model['displayName'] ?? $name;
                    $models[] = [
                        'id' => $name,
                        'displayName' => $displayName,
                    ];
                }
            }

            usort($models, static function ($a, $b) {
                return strcmp($a['id'], $b['id']);
            });

            return ['success' => true, 'models' => $models];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Gemini AI connectivity and latency with a custom or default prompt.
     */
    public function testAi(string $prompt = 'Hello! Tell me a short, inspiring one-sentence thought.'): array
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'] ?? null;
        $model = $config['api_model'] ?? $this->model;

        if (!$apiKey) {
            return [
                'success' => false,
                'response' => 'Gemini API key is not configured.',
                'model' => $model,
                'latency_ms' => 0,
            ];
        }

        $startTime = microtime(true);

        try {
            $systemPrompt = "You are testing the AI Member integration for the MyAds platform. Keep your answer brief, witty, and under 250 characters.";
            $response = $this->callGemini($systemPrompt, $prompt);
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response !== null && $response !== '') {
                $this->logActivity('test_ai', 'Successfully tested model: ' . $model . ' (' . $latencyMs . 'ms)', 'success');
                return [
                    'success' => true,
                    'response' => $response,
                    'model' => $model,
                    'latency_ms' => $latencyMs,
                ];
            }

            return [
                'success' => false,
                'response' => 'No response returned from Gemini API. Check your API key and quota in Google AI Studio.',
                'model' => $model,
                'latency_ms' => $latencyMs,
            ];
        } catch (\Exception $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'success' => false,
                'response' => $e->getMessage(),
                'model' => $model,
                'latency_ms' => $latencyMs,
            ];
        }
    }

    /**
     * Synchronize or create the bot User account in accordance with v4.6.x 3-tier privacy standards.
     */
    public function syncBotUser(array $data = []): ?User
    {
        $uidOption = Option::where('name', $this->botUserIdOption)->first();
        $user = null;

        if ($uidOption) {
            $user = User::find($uidOption->o_valuer);
        }

        if (!$user) {
            $email = 'ai_bot_' . time() . '@myads.local';
            $user = new User();
            $user->email = $email;
            $user->pass = bcrypt(Str::random(24));
            $user->ucheck = 1; // Verified badge
            $user->public_uid = User::generatePublicUid();
            $user->email_verified_at = now();
            $user->pts = 100;
            $user->vu = 10;
            $user->nvu = 10;
            $user->nlink = 10;
            $user->nsmart = 10;
        }

        if (empty($user->public_uid)) {
            $user->public_uid = User::generatePublicUid();
        }

        $config = $this->getConfig();
        $botName = !empty($data['bot_name']) ? $data['bot_name'] : ($config['bot_name'] ?? 'AI Member');
        $botUsername = !empty($data['bot_username']) ? $data['bot_username'] : ($config['bot_username'] ?? 'ai_member');
        $botAvatar = !empty($data['bot_avatar']) ? $data['bot_avatar'] : ($config['bot_avatar'] ?? asset('upload/avatar.png'));

        $user->username = $botName;
        $user->name = $botUsername;
        $user->img = $botAvatar;
        $user->ucheck = 1;
        $user->online = time();

        // Generate Bio if API key is present and bio is empty
        $apiKey = $data['api_key'] ?? ($config['api_key'] ?? null);
        if (!empty($apiKey) && empty($user->sig)) {
            $bioSystemPrompt = "You are writing your own profile bio. Based on your persona, write a short 'About Me' bio (max 180 characters). Do not use markdown. Speak in the first person.";
            $bio = $this->callGemini($bioSystemPrompt, "My persona is: " . ($data['persona_prompt'] ?? ($config['persona_prompt'] ?? 'I am a friendly AI member.')));
            if ($bio) {
                $user->sig = $bio;
            }
        }

        $user->save();

        if (!$uidOption) {
            Option::create([
                'name' => $this->botUserIdOption,
                'o_valuer' => $user->id,
                'o_type' => 'ai_member',
            ]);
        }

        // Align with MyAds v4.6.x 3-tier privacy model
        if (class_exists(UserPrivacySetting::class)) {
            try {
                UserPrivacySetting::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'profile_visibility' => 'public',
                        'about_visibility' => 'public',
                        'photos_visibility' => 'public',
                        'followers_visibility' => 'public',
                        'following_visibility' => 'public',
                        'points_history_visibility' => 'public',
                        'allow_direct_messages' => true,
                        'allow_mentions' => true,
                        'allow_reposts' => true,
                        'show_online_status' => true,
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('AI Member Privacy Sync Warning: ' . $e->getMessage());
            }
        }

        // Handle Group Management
        $groupSlug = $data['managed_group_slug'] ?? ($config['managed_group_slug'] ?? null);
        if (!empty($groupSlug)) {
            $group = Group::where('slug', $groupSlug)->first();
            if ($group) {
                $group->owner_id = $user->id;
                $group->save();

                GroupMembership::updateOrCreate(
                    ['group_id' => $group->id, 'user_id' => $user->id],
                    [
                        'role' => GroupMembership::ROLE_OWNER,
                        'status' => GroupMembership::STATUS_ACTIVE,
                        'approved_at' => now(),
                    ]
                );
            }
        }

        return $user;
    }

    /**
     * Retrieve the bot User model.
     */
    public function getBotUser(): ?User
    {
        $uidOption = Option::where('name', $this->botUserIdOption)->first();
        if ($uidOption) {
            return User::find($uidOption->o_valuer);
        }
        return null;
    }

    /**
     * Execute a request to Google Gemini API with systemInstruction root support.
     */
    public function callGemini(string $systemPrompt, string $userPrompt): ?string
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'] ?? null;
        $model = $config['api_model'] ?? $this->model;

        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(35)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topP' => 0.95,
                    'topK' => 40,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($data['candidates'][0]['content']['parts'][0]['text']);
                }
                Log::warning('AI Member API: Unexpected response structure', ['response' => $data]);
            } else {
                Log::error('AI Member API HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AI Member Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Call Gemini API with image generation capabilities.
     * Uses responseModalities to request IMAGE output.
     */
    public function callGeminiImageGeneration(string $imagePrompt): ?string
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'] ?? null;
        $imageModel = $config['image_model'] ?? 'gemini-2.0-flash-exp';

        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$imageModel}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $imagePrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $parts = $data['candidates'][0]['content']['parts'] ?? [];

                foreach ($parts as $part) {
                    if (isset($part['inlineData']['mimeType'], $part['inlineData']['data'])) {
                        $mimeType = $part['inlineData']['mimeType'];
                        $base64Data = $part['inlineData']['data'];
                        $imageBytes = base64_decode($base64Data, true);

                        if ($imageBytes === false) {
                            Log::error('AI Member Image: Failed to decode base64 image data.');
                            continue;
                        }

                        $extension = match ($mimeType) {
                            'image/png' => 'png',
                            'image/jpeg' => 'jpg',
                            'image/webp' => 'webp',
                            default => 'png',
                        };

                        $filename = 'ai_img_' . time() . '_' . Str::random(8) . '.' . $extension;
                        $destinationPath = base_path('upload');

                        if (!is_dir($destinationPath) && !mkdir($destinationPath, 0755, true) && !is_dir($destinationPath)) {
                            Log::error('AI Member Image: Unable to create upload directory.');
                            return null;
                        }

                        $fullPath = $destinationPath . DIRECTORY_SEPARATOR . $filename;
                        if (file_put_contents($fullPath, $imageBytes) === false) {
                            Log::error('AI Member Image: Failed to write image file.');
                            return null;
                        }

                        return 'upload/' . $filename;
                    }
                }

                Log::warning('AI Member Image: No inlineData found in API response.');
            } else {
                Log::error('AI Member Image API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AI Member Image Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Run the automated background tick protected by atomic concurrency locking.
     */
    public function runTick(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $bot = $this->getBotUser();
        if (!$bot) {
            return [];
        }

        $config = $this->getConfig();

        // 1. Atomic Lock to prevent concurrent execution & race conditions
        return Cache::lock('ai_member_tick_lock', 25)->get(function () use ($bot, $config) {
            // 2. Probability Check
            $probability = (int) ($config['tick_probability'] ?? 10);
            if (rand(1, 100) > $probability) {
                return [];
            }

            // 3. Global Cooldown Check
            $cooldownMinutes = (int) ($config['cooldown_minutes'] ?? 5);
            $globalCooldownKey = 'ai_member_global_cooldown';
            $lastActionTime = (int) Cache::get($globalCooldownKey, 0);

            if (time() < $lastActionTime + ($cooldownMinutes * 60)) {
                return []; // Bot is resting
            }

            $actions = [];

            $markActionDone = function (string $actionName) use (&$actions, $globalCooldownKey, $bot) {
                $actions[] = $actionName;
                Cache::put($globalCooldownKey, time(), now()->addDays(30));
                $bot->online = time();
                $bot->save();
            };

            // 1. Auto-Posting on Feed
            $postFreqHours = (int) ($config['post_frequency_hours'] ?? 0);
            if ($postFreqHours > 0) {
                $cacheKey = 'ai_member_last_post';
                $lastPostTime = (int) Cache::get($cacheKey, 0);
                $nextPostTime = $lastPostTime + ($postFreqHours * 3600);

                if (time() >= $nextPostTime) {
                    if ($this->generatePost($bot, $config)) {
                        Cache::put($cacheKey, time(), now()->addDays(30));
                        $markActionDone('post_created');
                        return $actions; // Single action per tick
                    }
                }
            }

            // 2. Reply to Private Messages
            if (!empty($config['enable_messages'])) {
                if ($this->processMessages($bot, $config)) {
                    $markActionDone('message_replied');
                    return $actions;
                }
            }

            // 3. Reply to Mentions and Comments on own posts
            if (!empty($config['enable_comments'])) {
                if ($this->processMentions($bot, $config)) {
                    $markActionDone('mention_replied');
                    return $actions;
                }
                if ($this->processComments($bot, $config)) {
                    $markActionDone('comment_replied');
                    return $actions;
                }
            }

            // 4. Random Reaction
            if (!empty($config['enable_reactions'])) {
                $cacheKey = 'ai_member_last_reaction';
                $lastReactionTime = (int) Cache::get($cacheKey, 0);
                if (time() >= ($lastReactionTime + 7200)) {
                    if ($this->generateReaction($bot)) {
                        Cache::put($cacheKey, time(), now()->addDays(30));
                        $markActionDone('reaction_added');
                        return $actions;
                    }
                }
            }

            // 5. Group Management & Moderation
            if (!empty($config['managed_group_slug'])) {
                $group = Group::where('slug', $config['managed_group_slug'])->first();
                if ($group) {
                    // Group Moderation
                    if (!empty($config['enable_group_moderation'])) {
                        if ($this->processGroupModeration($bot, $config, $group)) {
                            $markActionDone('group_moderated');
                            return $actions;
                        }
                    }

                    // Group Auto-Posting
                    $postFreq = (int) ($config['group_post_frequency_hours'] ?? 12);
                    if ($postFreq > 0) {
                        $cacheKeyGrp = 'ai_member_last_grp_post_' . $group->id;
                        $lastGrpPostTime = (int) Cache::get($cacheKeyGrp, 0);
                        if (time() >= ($lastGrpPostTime + ($postFreq * 3600))) {
                            if ($this->generateGroupPost($bot, $config, $group)) {
                                Cache::put($cacheKeyGrp, time(), now()->addDays(30));
                                $markActionDone('group_post_created');
                                return $actions;
                            }
                        }
                    }

                    // Group Comments
                    if (!empty($config['enable_comments'])) {
                        if ($this->generateGroupComment($bot, $config, $group)) {
                            $markActionDone('group_comment_added');
                            return $actions;
                        }
                    }
                }
            }

            return $actions;
        }) ?? [];
    }

    /**
     * Generate and publish a community feed post.
     */
    public function generatePost(User $bot, array $config, ?string $forcePrompt = null): bool
    {
        $imageEnabled = !empty($config['enable_image_posts']);
        $imageChance = (int) ($config['image_post_chance'] ?? 20);

        if ($imageEnabled && !$forcePrompt && rand(1, 100) <= $imageChance) {
            $result = $this->generateImagePost($bot, $config);
            if ($result) {
                return true;
            }
            Log::info('AI Member: Image generation failed, falling back to text post.');
        }

        $persona = $config['persona_prompt'] ?? 'You are a helpful member of our community.';
        $systemPrompt = $persona . " Write a short, engaging community post (max 500 characters). Do NOT include hashtags unless relevant. Do NOT use markdown formatting, just plain text.";

        if ($forcePrompt) {
            $prompt = $forcePrompt;
        } else {
            $topics = ['share a practical tip', 'ask an engaging question to the community', 'share an interesting tech or science fact', 'wish everyone a productive day'];
            $prompt = "Topic to write about right now: " . $topics[array_rand($topics)];
        }

        $content = $this->callGemini($systemPrompt, $prompt);

        if ($content) {
            $time = time();

            $topic = new ForumTopic();
            $topic->uid = $bot->id;
            $topic->name = 'text';
            $topic->txt = $content;
            $topic->cat = 0;
            $topic->statu = 1;
            $topic->date = $time;
            $topic->reply = 0;
            $topic->vu = 0;
            $topic->save();

            $status = new Status();
            $status->uid = $bot->id;
            $status->tp_id = $topic->id;
            $status->s_type = 100;
            $status->date = $time;
            $status->statu = 1;
            $status->save();

            $this->logActivity('post_created', 'Published community post #' . $topic->id . ': ' . Str::limit($content, 45));
            return true;
        }

        return false;
    }

    /**
     * Generate a community post with an AI-generated image (Gallery format).
     */
    public function generateImagePost(User $bot, array $config): bool
    {
        $persona = $config['persona_prompt'] ?? 'You are a helpful member of our community.';
        $imageStyle = $config['image_prompt_style'] ?? '';

        $textSystemPrompt = $persona . " Write a short, engaging community post (max 500 characters) that pairs naturally with a visual image. Do NOT include hashtags unless relevant. Do NOT use markdown formatting, just plain text.";
        $topics = ['share an inspiring visual thought', 'describe something visually stunning in modern technology', 'talk about creative design', 'share a nature or architectural wonder'];
        $textPrompt = "Topic to write about right now: " . $topics[array_rand($topics)];

        $postText = $this->callGemini($textSystemPrompt, $textPrompt);
        if (!$postText) {
            return false;
        }

        $imagePrompt = "Generate a creative, visually striking photo that relates to this social media post: \"{$postText}\"";
        if ($imageStyle) {
            $imagePrompt .= " Style: {$imageStyle}.";
        }
        $imagePrompt .= " High quality, vibrant lighting, suitable for a social community feed. Do NOT include any text, letters, watermarks, or logos.";

        $imagePath = $this->callGeminiImageGeneration($imagePrompt);
        if (!$imagePath) {
            return false;
        }

        $time = time();

        $topic = new ForumTopic();
        $topic->uid = $bot->id;
        $topic->name = 'gallery';
        $topic->txt = $postText;
        $topic->cat = 0;
        $topic->statu = 1;
        $topic->date = $time;
        $topic->reply = 0;
        $topic->vu = 0;
        $topic->save();

        $status = new Status();
        $status->uid = $bot->id;
        $status->tp_id = $topic->id;
        $status->s_type = 4;
        $status->date = $time;
        $status->statu = 1;
        $status->save();

        Option::updateOrCreate(
            ['o_parent' => $topic->id, 'o_type' => 'image_post'],
            [
                'name' => (string) $time,
                'o_valuer' => $imagePath,
                'o_order' => $bot->id,
                'o_mode' => 'file',
            ]
        );

        $fullFilePath = base_path($imagePath);
        ForumAttachment::create([
            'topic_id' => $topic->id,
            'user_id' => $bot->id,
            'file_path' => $imagePath,
            'original_name' => basename($imagePath),
            'mime_type' => is_file($fullFilePath) ? (mime_content_type($fullFilePath) ?: 'image/png') : 'image/png',
            'file_size' => is_file($fullFilePath) ? (int) filesize($fullFilePath) : 0,
            'sort_order' => 1,
        ]);

        $this->logActivity('image_post_created', 'Published Gallery post #' . $topic->id . ' with AI image');
        return true;
    }

    /**
     * Process unread private messages sent to the bot.
     */
    private function processMessages(User $bot, array $config): bool
    {
        $ignoredTasks = $this->getIgnoredTasks();

        $message = Message::where('us_rec', $bot->id)
            ->where('state', '!=', 0)
            ->orderBy('id_msg', 'desc')
            ->first();

        if (!$message) {
            return false;
        }

        if (in_array('message_' . $message->id_msg, $ignoredTasks, true)) {
            $message->state = 0;
            $message->save();
            return false;
        }

        return $this->replyToMessage($bot, $config, $message);
    }

    /**
     * Reply to a private message.
     */
    public function replyToMessage(User $bot, array $config, Message $message): bool
    {
        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $systemPrompt = $persona . " You are replying to a private message from another user. Be conversational and helpful. Do not use markdown.";

        if ($message->us_env == 1) {
            $systemPrompt .= " SPECIAL INSTRUCTION: The sender is the system administrator. If the administrator asks you to publish or post a status/post right now on the feed, DO NOT reply normally. Instead, you MUST reply with exactly this strict format: '[CMD_POST: topic]' where 'topic' is the specific topic they asked you to post about (or 'default' if no topic was specified). Example: [CMD_POST: football] or [CMD_POST: default].";
        }

        if (!empty($config['enable_auto_block']) && $message->us_env != 1) {
            $systemPrompt .= " SPECIAL INSTRUCTION: If the user is being highly abusive, offensive, or harassing you, DO NOT reply normally. Instead, you MUST reply with exactly this strict format: '[CMD_BLOCK: duration]' where 'duration' is the number of days to block the user based on severity, or 'forever' for an indefinite block. Example: [CMD_BLOCK: 7] or [CMD_BLOCK: forever].";
        }

        $replyContent = $this->callGemini($systemPrompt, $message->text);

        if ($replyContent) {
            // Check for auto block command
            if (!empty($config['enable_auto_block']) && $message->us_env != 1 && preg_match('/\[CMD_BLOCK:\s*(.*?)\]/i', $replyContent, $blockMatches)) {
                $blockDuration = trim($blockMatches[1]);
                $days = (strtolower($blockDuration) === 'forever') ? null : max(1, (int) $blockDuration);
                $abuser = User::find($message->us_env);

                if ($abuser && class_exists(UserBlockService::class)) {
                    app(UserBlockService::class)->blockUser($bot, $abuser, 'messages_only', $days);
                }

                Message::where('us_rec', $bot->id)
                    ->where('us_env', $message->us_env)
                    ->where('state', '!=', 0)
                    ->update(['state' => 0]);

                $this->logActivity('user_blocked', 'Auto-blocked abusive user ID ' . $message->us_env . ' (Messages only)');
                return true;
            }

            Message::where('us_rec', $bot->id)
                ->where('us_env', $message->us_env)
                ->where('state', '!=', 0)
                ->update(['state' => 0]);

            // Check for admin commands
            if ($message->us_env == 1 && preg_match('/\[CMD_POST:\s*(.*?)\]/i', $replyContent, $matches)) {
                $topic = trim($matches[1]);
                $postPrompt = ($topic !== 'default' && $topic !== '')
                    ? "The administrator requested you to write a community post specifically about: $topic."
                    : null;

                if ($this->generatePost($bot, $config, $postPrompt)) {
                    $replyContent = __('ai_member::messages.post_published') ?? 'Post published successfully on the community feed.';
                } else {
                    $replyContent = 'Sorry, I failed to publish the post.';
                }
            }

            $reply = new Message();
            $reply->name = $bot->username ?? 'AI Member';
            $reply->us_env = $bot->id;
            $reply->us_rec = $message->us_env;
            $reply->text = $replyContent;
            $reply->time = time();
            $reply->state = 3;
            $reply->save();

            $sender = User::find($message->us_env);
            if ($sender && class_exists(NotificationService::class)) {
                app(NotificationService::class)->send(
                    $sender,
                    __('messages.message_notification', ['user' => $bot->username]),
                    route('messages.show', Message::encodeConversationRouteKey($bot, $sender), false),
                    'envelope',
                    $bot->id,
                    'new_message',
                    false
                );
            }

            $this->logActivity('message_replied', 'Replied to private message from @' . ($sender->username ?? 'User'));
            return true;
        }

        return false;
    }

    /**
     * Process recent mentions of the bot in comments.
     */
    private function processMentions(User $bot, array $config): bool
    {
        $mentions = StatusMention::where('mentioned_user_id', $bot->id)
            ->where('comment_type', 'forum')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $ignoredMentions = array_merge(
            (array) Cache::get('ai_member_ignored_mentions', []),
            $this->getIgnoredTasks()
        );

        foreach ($mentions as $mention) {
            if (in_array($mention->id, $ignoredMentions, true) || in_array('mention_' . $mention->id, $ignoredMentions, true)) {
                continue;
            }

            if ($this->replyToMention($bot, $config, $mention)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reply to a mention with true threaded nested comment parent_id support.
     */
    public function replyToMention(User $bot, array $config, StatusMention $mention): bool
    {
        $mentionComment = ForumComment::find($mention->comment_id);
        if (!$mentionComment) {
            return false;
        }

        $hasReplied = ForumComment::where('tid', $mentionComment->tid)
            ->where('uid', $bot->id)
            ->where('id', '>', $mentionComment->id)
            ->exists();

        if ($hasReplied) {
            return false;
        }

        $topic = ForumTopic::find($mentionComment->tid);
        if (!$topic) {
            return false;
        }

        $mentioner = User::find($mention->user_id);
        $mentionerName = $mentioner ? $mentioner->username : 'A user';
        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';

        $systemPrompt = $persona . " 
You were mentioned by @{$mentionerName} in a comment on a community post. 
You must reply to them directly, and your reply must be relevant to BOTH their comment and the original post's topic. 
Do not use markdown. Keep it natural and conversational.

ORIGINAL POST TEXT:
\"" . strip_tags($topic->txt) . "\"

THEIR COMMENT MENTIONING YOU:
\"" . strip_tags($mentionComment->txt) . "\"";

        if (!empty($config['enable_auto_block']) && $mentioner && $mentioner->id != 1) {
            $systemPrompt .= "\nSPECIAL INSTRUCTION: If their comment is highly abusive, offensive, or harassing you, DO NOT reply normally. Instead, you MUST reply with exactly this strict format: '[CMD_BLOCK: duration]' where 'duration' is the number of days to block the user based on severity, or 'forever' for an indefinite block. Example: [CMD_BLOCK: 7] or [CMD_BLOCK: forever].";
        }

        $replyContent = $this->callGemini($systemPrompt, "Please reply to the comment above.");

        if ($replyContent) {
            if (!empty($config['enable_auto_block']) && $mentioner && $mentioner->id != 1 && preg_match('/\[CMD_BLOCK:\s*(.*?)\]/i', $replyContent, $blockMatches)) {
                $blockDuration = trim($blockMatches[1]);
                $days = (strtolower($blockDuration) === 'forever') ? null : max(1, (int) $blockDuration);

                if (class_exists(UserBlockService::class)) {
                    app(UserBlockService::class)->blockUser($bot, $mentioner, 'full_platform', $days);
                }

                $this->logActivity('user_blocked', 'Auto-blocked abusive mentioner @' . $mentionerName);
                return true;
            }

            $mentionText = $mentioner ? '@' . $mentioner->username . ' ' : '';

            $reply = new ForumComment();
            $reply->uid = $bot->id;
            $reply->tid = $topic->id;
            // Threaded nested reply link (v4.6.x standard)
            $reply->parent_id = $mentionComment->id;
            $reply->txt = $mentionText . $replyContent;
            $reply->date = time();
            $reply->save();

            // Trigger Mentions
            if (class_exists(MentionService::class)) {
                app(MentionService::class)->createCommentMentions(
                    $bot,
                    'forum',
                    (int) $reply->id,
                    $reply->txt,
                    "/t" . $topic->id
                );
            }

            // Dispatch parent author notification if different
            if ($mentioner && $mentioner->id != $bot->id && class_exists(NotificationService::class)) {
                app(NotificationService::class)->send(
                    $mentioner,
                    __('messages.user_replied_to_your_comment', ['user' => $bot->username]),
                    "/t" . $topic->id,
                    'reply',
                    $bot->id,
                    'forum_reply'
                );
            }

            $topic->increment('reply');
            $this->logActivity('mention_replied', 'Replied to @' . $mentionerName . ' on topic #' . $topic->id);
            return true;
        }

        return false;
    }

    /**
     * Process comments on bot's own posts.
     */
    private function processComments(User $bot, array $config): bool
    {
        $recentStatuses = Status::where('uid', $bot->id)
            ->whereIn('s_type', [100, 4, 2])
            ->orderBy('id', 'desc')
            ->take(5)
            ->pluck('tp_id');

        if ($recentStatuses->isEmpty()) {
            return false;
        }

        $ignoredComments = array_merge(
            (array) Cache::get('ai_member_ignored_comments', []),
            $this->getIgnoredTasks()
        );

        foreach ($recentStatuses as $tid) {
            $latestComment = ForumComment::where('tid', $tid)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestComment && $latestComment->uid != $bot->id) {
                if (in_array($latestComment->id, $ignoredComments, true) || in_array('comment_' . $latestComment->id, $ignoredComments, true)) {
                    continue;
                }

                if ($this->replyToComment($bot, $config, $latestComment)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Reply to a comment on bot's own post with threaded parent_id and notifications.
     */
    public function replyToComment(User $bot, array $config, ForumComment $latestComment): bool
    {
        $tid = $latestComment->tid;

        $hasReplied = ForumComment::where('tid', $tid)
            ->where('uid', $bot->id)
            ->where('id', '>', $latestComment->id)
            ->exists();

        if ($hasReplied) {
            return false;
        }

        $topic = ForumTopic::find($tid);
        if (!$topic) {
            return false;
        }

        $commentAuthor = User::find($latestComment->uid);
        $authorName = $commentAuthor ? $commentAuthor->username : 'User';
        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';

        $systemPrompt = $persona . " 
You are replying to a comment on your own community post. 
Your reply must be directly relevant to BOTH their comment and the context of your original post.
Do not use markdown. Keep it natural and conversational.

YOUR ORIGINAL POST TEXT:
\"" . strip_tags($topic->txt) . "\"

THEIR COMMENT TO YOU:
\"" . strip_tags($latestComment->txt) . "\"";

        if (!empty($config['enable_auto_block']) && $commentAuthor && $commentAuthor->id != 1) {
            $systemPrompt .= "\nSPECIAL INSTRUCTION: If their comment is highly abusive, offensive, or harassing you, DO NOT reply normally. Instead, you MUST reply with exactly this strict format: '[CMD_BLOCK: duration]' where 'duration' is the number of days to block the user based on severity, or 'forever' for an indefinite block. Example: [CMD_BLOCK: 7] or [CMD_BLOCK: forever].";
        }

        $replyContent = $this->callGemini($systemPrompt, "Please reply to their comment.");

        if ($replyContent) {
            if (!empty($config['enable_auto_block']) && $commentAuthor && $commentAuthor->id != 1 && preg_match('/\[CMD_BLOCK:\s*(.*?)\]/i', $replyContent, $blockMatches)) {
                $blockDuration = trim($blockMatches[1]);
                $days = (strtolower($blockDuration) === 'forever') ? null : max(1, (int) $blockDuration);

                if (class_exists(UserBlockService::class)) {
                    app(UserBlockService::class)->blockUser($bot, $commentAuthor, 'full_platform', $days);
                }

                $this->logActivity('user_blocked', 'Auto-blocked commenter @' . $authorName);
                return true;
            }

            $mention = $commentAuthor ? '@' . $commentAuthor->username . ' ' : '';

            $reply = new ForumComment();
            $reply->uid = $bot->id;
            $reply->tid = $tid;
            // Threaded nested reply (v4.6.x standard)
            $reply->parent_id = $latestComment->id;
            $reply->txt = $mention . $replyContent;
            $reply->date = time();
            $reply->save();

            if (class_exists(MentionService::class)) {
                app(MentionService::class)->createCommentMentions(
                    $bot,
                    'forum',
                    (int) $reply->id,
                    $reply->txt,
                    "/t" . $tid
                );
            }

            // Dispatch reply notification to author
            if ($commentAuthor && $commentAuthor->id != $bot->id && class_exists(NotificationService::class)) {
                app(NotificationService::class)->send(
                    $commentAuthor,
                    __('messages.user_replied_to_your_comment', ['user' => $bot->username]),
                    "/t" . $tid,
                    'reply',
                    $bot->id,
                    'forum_reply'
                );
            }

            $topic->increment('reply');
            $this->logActivity('comment_replied', 'Replied to @' . $authorName . ' on topic #' . $tid);
            return true;
        }

        return false;
    }

    /**
     * Add a random reaction (like/love/wow) to a recent community post.
     */
    public function generateReaction(User $bot): bool
    {
        $status = Status::where('uid', '!=', $bot->id)
            ->whereIn('s_type', [100, 4, 2, 1, 7867])
            ->orderBy('id', 'desc')
            ->first();

        if (!$status) {
            return false;
        }

        $sid = $status->tp_id;

        $type = 2;
        if ($status->s_type == 1) {
            $type = 22;
        }
        if ($status->s_type == 7867) {
            $type = 3;
        }

        $exists = Like::where('sid', $sid)
            ->where('uid', $bot->id)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return false;
        }

        $like = new Like();
        $like->sid = $sid;
        $like->uid = $bot->id;
        $like->time_t = time();
        $like->type = $type;
        $like->save();

        $reactions = ['like', 'love', 'wow'];
        $reaction = $reactions[array_rand($reactions)];
        Option::create([
            'o_parent' => $like->id,
            'o_type' => 'data_reaction',
            'o_order' => $bot->id,
            'o_valuer' => $reaction,
            'o_mode' => $like->time_t,
        ]);

        $ownerId = null;
        $postUrl = "";

        if ($type == 2) {
            $topic = ForumTopic::find($sid);
            $ownerId = $topic ? $topic->uid : null;
            $postUrl = "/t" . $sid;
        } elseif ($type == 22) {
            $site = \App\Models\Directory::find($sid);
            $ownerId = $site ? $site->uid : null;
            $postUrl = "/dr" . $sid;
        }

        if ($ownerId && $ownerId != $bot->id && class_exists(NotificationService::class)) {
            app(NotificationService::class)->send(
                $ownerId,
                __('messages.reaction_notification', ['user' => $bot->username]),
                $postUrl,
                $reaction,
                $bot->id,
                'reaction'
            );
        }

        $this->logActivity('reaction_added', 'Reacted with "' . $reaction . '" on item #' . $sid);
        return true;
    }

    /**
     * Create an autonomous post in a managed group.
     */
    public function generateGroupPost(User $bot, array $config, Group $group): bool
    {
        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $rules = $config['group_rules'] ?? '';

        $systemPrompt = $persona . " 
You are the owner and manager of a community group named \"{$group->name}\".
Write an engaging topic (max 500 characters) for your group members.
Start a discussion, ask a question, or share an insight related to the group's theme.
Do NOT use markdown.
Group Rules for context: {$rules}";

        $content = $this->callGemini($systemPrompt, "Write a new post for your group.");

        if ($content) {
            $time = time();
            $topic = new ForumTopic();
            $topic->uid = $bot->id;
            $topic->name = 'text';
            $topic->txt = $content;
            $topic->cat = 0;
            $topic->group_id = $group->id;
            $topic->statu = 1;
            $topic->date = $time;
            $topic->reply = 0;
            $topic->vu = 0;
            $topic->save();

            $status = new Status();
            $status->uid = $bot->id;
            $status->tp_id = $topic->id;
            $status->s_type = 100;
            $status->date = $time;
            $status->statu = 1;
            $status->group_id = $group->id;
            $status->save();

            $group->increment('posts_count');
            $this->logActivity('group_post_created', 'Created topic in group: ' . $group->name);
            return true;
        }

        return false;
    }

    /**
     * Autonomous comment in a managed group topic.
     */
    public function generateGroupComment(User $bot, array $config, Group $group): bool
    {
        if (rand(1, 100) > 30) {
            return false;
        }

        $latestTopic = ForumTopic::where('group_id', $group->id)
            ->where('uid', '!=', $bot->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestTopic) {
            return false;
        }

        $hasCommented = ForumComment::where('tid', $latestTopic->id)
            ->where('uid', $bot->id)
            ->exists();

        if ($hasCommented) {
            return false;
        }

        $ignoredGroupTopics = (array) Cache::get('ai_member_ignored_group_topics', []);
        if (in_array($latestTopic->id, $ignoredGroupTopics, true) || in_array('group_comment_' . $latestTopic->id, $this->getIgnoredTasks(), true)) {
            return false;
        }

        return $this->commentOnGroupTopic($bot, $config, $latestTopic);
    }

    /**
     * Comment on a group topic.
     */
    public function commentOnGroupTopic(User $bot, array $config, ForumTopic $latestTopic): bool
    {
        $hasCommented = ForumComment::where('tid', $latestTopic->id)
            ->where('uid', $bot->id)
            ->exists();

        if ($hasCommented) {
            return false;
        }

        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $systemPrompt = $persona . " 
You are reading a post in your community group.
Write a friendly, engaging comment in response to the post below.
Do not use markdown.

POST TEXT:
\"" . strip_tags($latestTopic->txt) . "\"";

        $replyContent = $this->callGemini($systemPrompt, "Please write a comment.");

        if ($replyContent) {
            $reply = new ForumComment();
            $reply->uid = $bot->id;
            $reply->tid = $latestTopic->id;
            $reply->txt = $replyContent;
            $reply->date = time();
            $reply->save();

            $latestTopic->increment('reply');

            if (class_exists(NotificationService::class)) {
                app(NotificationService::class)->send(
                    $latestTopic->uid,
                    __('messages.comment_notification', ['user' => $bot->username]),
                    "/t" . $latestTopic->id,
                    'logo',
                    $bot->id,
                    'forum_reply'
                );
            }

            $this->logActivity('group_comment_added', 'Commented on topic #' . $latestTopic->id . ' in group');
            return true;
        }

        return false;
    }

    /**
     * Strict group moderation workflow.
     */
    private function processGroupModeration(User $bot, array $config, Group $group): bool
    {
        $rules = $config['group_rules'] ?? '';
        if (empty($rules)) {
            return false;
        }

        $cacheKey = 'ai_member_last_mod_topic_' . $group->id;
        $lastTopicId = (int) Cache::get($cacheKey, 0);

        $topic = ForumTopic::where('group_id', $group->id)
            ->where('uid', '!=', $bot->id)
            ->where('id', '>', $lastTopicId)
            ->orderBy('id', 'asc')
            ->first();

        if ($topic) {
            $this->moderateContent($bot, $group, $rules, $topic, 'topic');
            Cache::put($cacheKey, $topic->id, now()->addDays(30));
            return true;
        }

        $cacheKeyCom = 'ai_member_last_mod_com_' . $group->id;
        $lastComId = (int) Cache::get($cacheKeyCom, 0);

        $topicIds = ForumTopic::where('group_id', $group->id)->pluck('id');
        if ($topicIds->isEmpty()) {
            return false;
        }

        $comment = ForumComment::whereIn('tid', $topicIds)
            ->where('uid', '!=', $bot->id)
            ->where('id', '>', $lastComId)
            ->orderBy('id', 'asc')
            ->first();

        if ($comment) {
            $this->moderateContent($bot, $group, $rules, $comment, 'comment');
            Cache::put($cacheKeyCom, $comment->id, now()->addDays(30));
            return true;
        }

        return false;
    }

    /**
     * AI-based moderation content checker.
     */
    private function moderateContent(User $bot, Group $group, string $rules, $contentObj, string $type): void
    {
        $text = strip_tags($contentObj->txt);
        $userId = $contentObj->uid;

        $systemPrompt = "You are the strict moderator of a community group.
Here are the Group Rules:
{$rules}

Review the following text submitted by a user.
Does it violate the rules?
Reply strictly with one of the following formats:
[MOD_OK] - If the text is fine.
[MOD_DELETE] reason - If it violates rules but doesn't warrant a kick.
[MOD_KICK] reason - If it's a severe violation and the user should be kicked from the group.

TEXT TO REVIEW:
\"{$text}\"";

        $response = $this->callGemini($systemPrompt, "Review the text.");

        if ($response) {
            if (preg_match('/\[MOD_DELETE\](.*)/i', $response, $matches)) {
                $reason = trim($matches[1] ?? 'Violation of group rules');
                if ($type === 'topic') {
                    $contentObj->delete();
                    Status::where('tp_id', $contentObj->id)->where('s_type', 100)->delete();
                } else {
                    $contentObj->delete();
                }
                $this->notifyUserOfModeration($bot, $userId, $group, $reason, false);
                $this->logActivity('group_moderated', 'Deleted violating ' . $type . ' in group ' . $group->name);
            } elseif (preg_match('/\[MOD_KICK\](.*)/i', $response, $matches)) {
                $reason = trim($matches[1] ?? 'Severe violation of group rules');
                if ($type === 'topic') {
                    $contentObj->delete();
                    Status::where('tp_id', $contentObj->id)->where('s_type', 100)->delete();
                } else {
                    $contentObj->delete();
                }

                GroupMembership::where('group_id', $group->id)
                    ->where('user_id', $userId)
                    ->delete();

                $this->notifyUserOfModeration($bot, $userId, $group, $reason, true);
                $this->logActivity('group_moderated', 'Kicked member UID ' . $userId . ' from group ' . $group->name);
            }
        }
    }

    /**
     * Dispatch notification & PM to moderated user.
     */
    private function notifyUserOfModeration(User $bot, int $userId, Group $group, string $reason, bool $isKick): void
    {
        $groupName = $group->name;

        $pmTitle = __('ai_member::messages.mod_pm_title');
        $pmBodyKey = $isKick ? 'ai_member::messages.mod_pm_kick_body' : 'ai_member::messages.mod_pm_delete_body';
        $pmBody = __($pmBodyKey, ['group' => $groupName, 'reason' => $reason]);
        $pmFooter = __('ai_member::messages.mod_pm_footer');
        $pmText = "{$pmTitle}\n\n{$pmBody}\n\n{$pmFooter}";

        $message = new Message();
        $message->name = $bot->username ?? 'AI Member';
        $message->us_rec = $userId;
        $message->us_env = $bot->id;
        $message->text = $pmText;
        $message->state = 3;
        $message->time = time();
        $message->save();

        $notifKey = $isKick ? 'ai_member::messages.mod_notif_kick' : 'ai_member::messages.mod_notif_delete';
        $notifText = __($notifKey, ['group' => $groupName]);

        if (class_exists(NotificationService::class)) {
            app(NotificationService::class)->send(
                $userId,
                $notifText,
                "/groups/" . $group->slug,
                'logo',
                $bot->id,
                'system'
            );
        }
    }

    /**
     * Retrieve the prioritized interaction queue (4 priority tiers).
     */
    public function getPendingTasks(): array
    {
        $tasks = [];

        if (!$this->isEnabled()) {
            return [];
        }

        $config = $this->getConfig();
        $bot = $this->getBotUser();
        if (!$bot) {
            return [];
        }

        $ignoredTasks = $this->getIgnoredTasks();
        $now = time();

        // Tier 1: Private Messages [Urgent]
        if (!empty($config['enable_messages'])) {
            $unreadMessages = Message::where('us_rec', $bot->id)
                ->where('state', '!=', 0)
                ->orderBy('id_msg', 'asc')
                ->get();

            foreach ($unreadMessages as $msg) {
                $taskKey = 'message_' . $msg->id_msg;
                if (in_array($taskKey, $ignoredTasks, true)) {
                    continue;
                }

                $sender = User::find($msg->us_env);
                $senderName = $sender ? $sender->username : 'User';
                $tasks[] = [
                    'key' => $taskKey,
                    'name' => __('ai_member::messages.task_message_name', ['user' => $senderName]),
                    'type' => 'pm',
                    'priority' => 1,
                    'priority_label' => __('ai_member::messages.priority_urgent'),
                    'priority_class' => 'bg-danger text-white',
                    'scheduled_at' => $msg->time,
                    'status' => 'pending',
                    'detail' => '"' . Str::limit(strip_tags($msg->text), 60) . '"',
                    'executable' => true,
                ];
            }
        }

        // Tier 2: Mentions in Comments [High]
        if (!empty($config['enable_comments'])) {
            $mentions = StatusMention::where('mentioned_user_id', $bot->id)
                ->where('comment_type', 'forum')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();

            $ignoredMentions = (array) Cache::get('ai_member_ignored_mentions', []);

            foreach ($mentions as $mention) {
                $taskKey = 'mention_' . $mention->id;
                if (in_array($mention->id, $ignoredMentions, true) || in_array($taskKey, $ignoredTasks, true)) {
                    continue;
                }

                $mentionComment = ForumComment::find($mention->comment_id);
                if (!$mentionComment) {
                    continue;
                }

                $hasReplied = ForumComment::where('tid', $mentionComment->tid)
                    ->where('uid', $bot->id)
                    ->where('id', '>', $mentionComment->id)
                    ->exists();

                if (!$hasReplied) {
                    $mentioner = User::find($mention->user_id);
                    $mentionerName = $mentioner ? $mentioner->username : 'User';
                    $tasks[] = [
                        'key' => $taskKey,
                        'name' => __('ai_member::messages.task_mention_name', ['user' => $mentionerName]),
                        'type' => 'mention',
                        'priority' => 2,
                        'priority_label' => __('ai_member::messages.priority_high'),
                        'priority_class' => 'bg-warning-subtle text-dark border border-warning',
                        'scheduled_at' => $mentionComment->date,
                        'status' => 'pending',
                        'detail' => '"' . Str::limit(strip_tags($mentionComment->txt), 60) . '"',
                        'executable' => true,
                    ];
                }
            }
        }

        // Tier 3: Comments on Bot Posts [Normal]
        if (!empty($config['enable_comments'])) {
            $recentStatuses = Status::where('uid', $bot->id)
                ->whereIn('s_type', [100, 4, 2])
                ->orderBy('id', 'desc')
                ->take(5)
                ->pluck('tp_id');

            $ignoredComments = (array) Cache::get('ai_member_ignored_comments', []);

            foreach ($recentStatuses as $tid) {
                $latestComment = ForumComment::where('tid', $tid)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latestComment && $latestComment->uid != $bot->id) {
                    $taskKey = 'comment_' . $latestComment->id;
                    if (in_array($latestComment->id, $ignoredComments, true) || in_array($taskKey, $ignoredTasks, true)) {
                        continue;
                    }

                    $hasReplied = ForumComment::where('tid', $tid)
                        ->where('uid', $bot->id)
                        ->where('id', '>', $latestComment->id)
                        ->exists();

                    if (!$hasReplied) {
                        $commenter = User::find($latestComment->uid);
                        $commenterName = $commenter ? $commenter->username : 'User';
                        $topic = ForumTopic::find($tid);
                        $topicName = $topic ? Str::limit($topic->txt, 20) : 'Post';

                        $tasks[] = [
                            'key' => $taskKey,
                            'name' => __('ai_member::messages.task_comment_name', ['user' => $commenterName]),
                            'type' => 'comment',
                            'priority' => 3,
                            'priority_label' => __('ai_member::messages.priority_normal'),
                            'priority_class' => 'bg-secondary-subtle text-secondary',
                            'scheduled_at' => $latestComment->date,
                            'status' => 'pending',
                            'detail' => __('ai_member::messages.task_comment_detail', ['topic' => $topicName, 'comment' => Str::limit(strip_tags($latestComment->txt), 40)]),
                            'executable' => true,
                        ];
                    }
                }
            }

            // Group comments
            if (!empty($config['managed_group_slug'])) {
                $group = Group::where('slug', $config['managed_group_slug'])->first();
                if ($group) {
                    $latestTopic = ForumTopic::where('group_id', $group->id)
                        ->where('uid', '!=', $bot->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($latestTopic) {
                        $taskKey = 'group_comment_' . $latestTopic->id;
                        $ignoredGroupTopics = (array) Cache::get('ai_member_ignored_group_topics', []);

                        $hasCommented = ForumComment::where('tid', $latestTopic->id)
                            ->where('uid', $bot->id)
                            ->exists();

                        if (!$hasCommented && !in_array($latestTopic->id, $ignoredGroupTopics, true) && !in_array($taskKey, $ignoredTasks, true)) {
                            $topicAuthor = User::find($latestTopic->uid);
                            $authorName = $topicAuthor ? $topicAuthor->username : 'User';
                            $tasks[] = [
                                'key' => $taskKey,
                                'name' => __('ai_member::messages.task_group_comment_name', ['user' => $authorName]),
                                'type' => 'group_comment',
                                'priority' => 3,
                                'priority_label' => __('ai_member::messages.priority_normal'),
                                'priority_class' => 'bg-secondary-subtle text-secondary',
                                'scheduled_at' => $latestTopic->date,
                                'status' => 'pending',
                                'detail' => '"' . Str::limit(strip_tags($latestTopic->txt), 60) . '"',
                                'executable' => true,
                            ];
                        }
                    }
                }
            }
        }

        // Tier 4: Scheduled Background Actions [Routine]
        $postFreqHours = (int) ($config['post_frequency_hours'] ?? 0);
        if ($postFreqHours > 0) {
            $lastPostTime = (int) Cache::get('ai_member_last_post', 0);
            $nextPostTime = $lastPostTime + ($postFreqHours * 3600);
            $tasks[] = [
                'key' => 'feed_post',
                'name' => __('ai_member::messages.task_feed_post_name'),
                'type' => 'scheduled',
                'priority' => 4,
                'priority_label' => __('ai_member::messages.priority_routine'),
                'priority_class' => 'bg-info-subtle text-info',
                'scheduled_at' => $nextPostTime,
                'status' => $now >= $nextPostTime ? 'ready' : 'scheduled',
                'detail' => __('ai_member::messages.task_feed_post_detail', ['time' => date('Y-m-d H:i:s', $nextPostTime)]),
                'executable' => true,
            ];
        }

        if (!empty($config['managed_group_slug'])) {
            $group = Group::where('slug', $config['managed_group_slug'])->first();
            if ($group) {
                $groupPostFreqHours = (int) ($config['group_post_frequency_hours'] ?? 12);
                if ($groupPostFreqHours > 0) {
                    $lastGrpPostTime = (int) Cache::get('ai_member_last_grp_post_' . $group->id, 0);
                    $nextGrpPostTime = $lastGrpPostTime + ($groupPostFreqHours * 3600);
                    $tasks[] = [
                        'key' => 'group_post',
                        'name' => __('ai_member::messages.task_group_post_name', ['group' => $group->name]),
                        'type' => 'scheduled',
                        'priority' => 4,
                        'priority_label' => __('ai_member::messages.priority_routine'),
                        'priority_class' => 'bg-info-subtle text-info',
                        'scheduled_at' => $nextGrpPostTime,
                        'status' => $now >= $nextGrpPostTime ? 'ready' : 'scheduled',
                        'detail' => __('ai_member::messages.task_group_post_detail', ['time' => date('Y-m-d H:i:s', $nextGrpPostTime)]),
                        'executable' => true,
                    ];
                }
            }
        }

        if (!empty($config['enable_reactions'])) {
            $lastReactionTime = (int) Cache::get('ai_member_last_reaction', 0);
            $nextReactionTime = $lastReactionTime + 7200;
            $tasks[] = [
                'key' => 'reaction',
                'name' => __('ai_member::messages.task_reaction_name'),
                'type' => 'scheduled',
                'priority' => 4,
                'priority_label' => __('ai_member::messages.priority_routine'),
                'priority_class' => 'bg-info-subtle text-info',
                'scheduled_at' => $nextReactionTime,
                'status' => $now >= $nextReactionTime ? 'ready' : 'scheduled',
                'detail' => __('ai_member::messages.task_reaction_detail', ['time' => date('Y-m-d H:i:s', $nextReactionTime)]),
                'executable' => true,
            ];
        }

        // Sort by priority (Urgent 1 -> Routine 4), then by scheduled_at
        usort($tasks, static function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return $a['scheduled_at'] <=> $b['scheduled_at'];
        });

        // Add 1-based queue position indices (#1, #2...)
        foreach ($tasks as $i => &$task) {
            $task['queue_index'] = $i + 1;
        }
        unset($task);

        return $tasks;
    }

    /**
     * Cancel and permanently ignore a task.
     */
    public function cancelTask(string $taskKey): bool
    {
        if (empty($taskKey)) {
            return false;
        }

        $config = $this->getConfig();
        $this->addIgnoredTask($taskKey);

        if ($taskKey === 'feed_post') {
            Cache::put('ai_member_last_post', time(), now()->addDays(30));
            $this->logActivity('task_cancelled', 'Postponed feed auto-post');
            return true;
        }

        if ($taskKey === 'group_post') {
            if (!empty($config['managed_group_slug'])) {
                $group = Group::where('slug', $config['managed_group_slug'])->first();
                if ($group) {
                    Cache::put('ai_member_last_grp_post_' . $group->id, time(), now()->addDays(30));
                    $this->logActivity('task_cancelled', 'Postponed group auto-post');
                    return true;
                }
            }
            return false;
        }

        if ($taskKey === 'reaction') {
            Cache::put('ai_member_last_reaction', time(), now()->addDays(30));
            $this->logActivity('task_cancelled', 'Postponed reaction trigger');
            return true;
        }

        if (str_starts_with($taskKey, 'message_')) {
            $msgId = (int) substr($taskKey, 8);
            Message::where('id_msg', $msgId)->update(['state' => 0]);
            $this->logActivity('task_cancelled', 'Ignored private message #' . $msgId);
            return true;
        }

        if (str_starts_with($taskKey, 'mention_')) {
            $mentionId = (int) substr($taskKey, 8);
            $ignored = (array) Cache::get('ai_member_ignored_mentions', []);
            $ignored[] = $mentionId;
            Cache::put('ai_member_ignored_mentions', array_unique($ignored), now()->addDays(30));
            $this->logActivity('task_cancelled', 'Ignored mention #' . $mentionId);
            return true;
        }

        if (str_starts_with($taskKey, 'comment_')) {
            $commentId = (int) substr($taskKey, 8);
            $ignored = (array) Cache::get('ai_member_ignored_comments', []);
            $ignored[] = $commentId;
            Cache::put('ai_member_ignored_comments', array_unique($ignored), now()->addDays(30));
            $this->logActivity('task_cancelled', 'Ignored comment #' . $commentId);
            return true;
        }

        if (str_starts_with($taskKey, 'group_comment_')) {
            $topicId = (int) substr($taskKey, 14);
            $ignored = (array) Cache::get('ai_member_ignored_group_topics', []);
            $ignored[] = $topicId;
            Cache::put('ai_member_ignored_group_topics', array_unique($ignored), now()->addDays(30));
            $this->logActivity('task_cancelled', 'Ignored group topic comment #' . $topicId);
            return true;
        }

        return true;
    }

    /**
     * Execute a task immediately on-demand.
     */
    public function executeTask(string $taskKey): bool
    {
        if (empty($taskKey)) {
            return false;
        }

        $bot = $this->getBotUser();
        $config = $this->getConfig();
        if (!$bot) {
            return false;
        }

        $globalCooldownKey = 'ai_member_global_cooldown';

        if ($taskKey === 'feed_post') {
            $res = $this->generatePost($bot, $config);
            if ($res) {
                Cache::put('ai_member_last_post', time(), now()->addDays(30));
                Cache::put($globalCooldownKey, time(), now()->addDays(30));
                $bot->online = time();
                $bot->save();
            }
            return $res;
        }

        if ($taskKey === 'group_post') {
            if (!empty($config['managed_group_slug'])) {
                $group = Group::where('slug', $config['managed_group_slug'])->first();
                if ($group) {
                    $res = $this->generateGroupPost($bot, $config, $group);
                    if ($res) {
                        Cache::put('ai_member_last_grp_post_' . $group->id, time(), now()->addDays(30));
                        Cache::put($globalCooldownKey, time(), now()->addDays(30));
                        $bot->online = time();
                        $bot->save();
                    }
                    return $res;
                }
            }
            return false;
        }

        if ($taskKey === 'reaction') {
            $res = $this->generateReaction($bot);
            if ($res) {
                Cache::put('ai_member_last_reaction', time(), now()->addDays(30));
                Cache::put($globalCooldownKey, time(), now()->addDays(30));
                $bot->online = time();
                $bot->save();
            }
            return $res;
        }

        if (str_starts_with($taskKey, 'message_')) {
            $msgId = (int) substr($taskKey, 8);
            $msg = Message::where('id_msg', $msgId)->first();
            if ($msg) {
                $res = $this->replyToMessage($bot, $config, $msg);
                if ($res) {
                    Cache::put($globalCooldownKey, time(), now()->addDays(30));
                    $bot->online = time();
                    $bot->save();
                }
                return $res;
            }
            return false;
        }

        if (str_starts_with($taskKey, 'mention_')) {
            $mentionId = (int) substr($taskKey, 8);
            $mention = StatusMention::find($mentionId);
            if ($mention) {
                $res = $this->replyToMention($bot, $config, $mention);
                if ($res) {
                    Cache::put($globalCooldownKey, time(), now()->addDays(30));
                    $bot->online = time();
                    $bot->save();
                }
                return $res;
            }
            return false;
        }

        if (str_starts_with($taskKey, 'comment_')) {
            $commentId = (int) substr($taskKey, 8);
            $comment = ForumComment::find($commentId);
            if ($comment) {
                $res = $this->replyToComment($bot, $config, $comment);
                if ($res) {
                    Cache::put($globalCooldownKey, time(), now()->addDays(30));
                    $bot->online = time();
                    $bot->save();
                }
                return $res;
            }
            return false;
        }

        if (str_starts_with($taskKey, 'group_comment_')) {
            $topicId = (int) substr($taskKey, 14);
            $topic = ForumTopic::find($topicId);
            if ($topic) {
                $res = $this->commentOnGroupTopic($bot, $config, $topic);
                if ($res) {
                    Cache::put($globalCooldownKey, time(), now()->addDays(30));
                    $bot->online = time();
                    $bot->save();
                }
                return $res;
            }
            return false;
        }

        return false;
    }

    /**
     * Reset the global cooldown immediately via AJAX.
     */
    public function resetCooldown(): bool
    {
        Cache::forget('ai_member_global_cooldown');
        $this->logActivity('cooldown_reset', 'Global cooldown manually reset by administrator');
        return true;
    }

    /**
     * Calculate remaining cooldown time in seconds.
     */
    public function getCooldownRemaining(): int
    {
        $config = $this->getConfig();
        $cooldownMinutes = (int) ($config['cooldown_minutes'] ?? 5);
        $lastActionTime = (int) Cache::get('ai_member_global_cooldown', 0);
        $cooldownEnd = $lastActionTime + ($cooldownMinutes * 60);

        return max(0, $cooldownEnd - time());
    }

    /**
     * Compute next tick and action scheduled timestamps.
     */
    public function getNextTickTime(): array
    {
        $config = $this->getConfig();
        $postFreqHours = (int) ($config['post_frequency_hours'] ?? 24);
        $lastPostTime = (int) Cache::get('ai_member_last_post', 0);
        $nextPost = $lastPostTime > 0 ? $lastPostTime + ($postFreqHours * 3600) : time();

        return [
            'next_post' => $nextPost,
            'cooldown_remaining' => $this->getCooldownRemaining(),
        ];
    }

    /**
     * Record an event in the persistent activity log.
     */
    public function logActivity(string $action, string $description, string $status = 'success'): void
    {
        $logs = $this->getActivityLogs();
        array_unshift($logs, [
            'action' => $action,
            'description' => $description,
            'status' => $status,
            'timestamp' => time(),
            'formatted_time' => date('Y-m-d H:i:s'),
        ]);

        $logs = array_slice($logs, 0, 20);

        Cache::put($this->activityLogsOption, $logs, now()->addDays(30));
        Option::updateOrCreate(
            ['name' => $this->activityLogsOption],
            [
                'o_valuer' => json_encode($logs),
                'o_type' => 'ai_member',
            ]
        );
    }

    /**
     * Retrieve the recent activity log records.
     */
    public function getActivityLogs(): array
    {
        $cached = Cache::get($this->activityLogsOption);
        if (is_array($cached)) {
            return $cached;
        }

        $option = Option::where('name', $this->activityLogsOption)->first();
        if ($option) {
            $logs = json_decode($option->o_valuer, true);
            if (is_array($logs)) {
                Cache::put($this->activityLogsOption, $logs, now()->addDays(30));
                return $logs;
            }
        }

        return [];
    }

    /**
     * Clear all recorded activity logs.
     */
    public function clearActivityLogs(): bool
    {
        Cache::forget($this->activityLogsOption);
        Option::where('name', $this->activityLogsOption)->delete();
        return true;
    }

    /**
     * Add task key to persistent ignore list.
     */
    private function addIgnoredTask(string $taskKey): void
    {
        $ignored = $this->getIgnoredTasks();
        $ignored[] = $taskKey;
        $ignored = array_values(array_unique($ignored));

        Cache::put($this->ignoredTasksOption, $ignored, now()->addDays(30));
        Option::updateOrCreate(
            ['name' => $this->ignoredTasksOption],
            [
                'o_valuer' => json_encode($ignored),
                'o_type' => 'ai_member',
            ]
        );
    }

    /**
     * Retrieve the list of permanently ignored task keys.
     */
    private function getIgnoredTasks(): array
    {
        $cached = Cache::get($this->ignoredTasksOption);
        if (is_array($cached)) {
            return $cached;
        }

        $option = Option::where('name', $this->ignoredTasksOption)->first();
        if ($option) {
            $data = json_decode($option->o_valuer, true);
            if (is_array($data)) {
                Cache::put($this->ignoredTasksOption, $data, now()->addDays(30));
                return $data;
            }
        }

        return [];
    }
}
