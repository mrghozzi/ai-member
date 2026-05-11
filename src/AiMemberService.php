<?php

namespace MyAds\Plugins\AiMember;

use App\Models\Option;
use App\Models\User;
use App\Models\Status;
use App\Models\Message;
use App\Models\ForumComment;
use App\Models\Like;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiMemberService
{
    private $model = 'gemini-3-flash-preview';
    private $botUserIdOption = 'ai_member_bot_uid';
    private $configOption = 'ai_member_config';

    public function getConfig()
    {
        $option = Option::where('name', $this->configOption)->first();
        return $option ? json_decode($option->o_valuer, true) : [];
    }

    public function saveConfig($data)
    {
        Option::updateOrCreate(
            ['name' => $this->configOption],
            [
                'o_valuer' => json_encode($data),
                'o_type' => 'ai_member'
            ]
        );
    }

    public function isEnabled()
    {
        $config = $this->getConfig();
        return !empty($config['is_enabled']) && !empty($config['api_key']);
    }

    public function syncBotUser($data)
    {
        $uidOption = Option::where('name', $this->botUserIdOption)->first();
        $user = null;

        if ($uidOption) {
            $user = User::find($uidOption->o_valuer);
        }

        if (!$user) {
            // Need to generate a unique email and fake password
            $email = 'ai_bot_' . time() . '@myads.local';
            $user = new User();
            $user->email = $email;
            $user->pass = bcrypt(\Illuminate\Support\Str::random(16));
            $user->ucheck = 1; // Verified badge
            $user->public_uid = User::generatePublicUid();
            $user->pts = 10;
            $user->vu = 10;
            $user->nvu = 10;
            $user->nlink = 10;
            $user->nsmart = 10;
        }

        $user->username = $data['bot_name'] ?? 'AI Member';
        $user->img = $data['bot_avatar'] ?? asset('upload/avatar.png');
        $user->online = time();

        // Generate Bio if API key is present
        if (!empty($data['api_key'])) {
            $bioSystemPrompt = "You are writing your own profile bio. Based on your persona, write a short 'About Me' bio (max 200 characters). Do not use markdown. Speak in the first person.";
            $bio = $this->callGemini($bioSystemPrompt, "My persona is: " . ($data['persona_prompt'] ?? 'I am a friendly AI member.'));
            if ($bio) {
                $user->sig = $bio;
            }
        }

        $user->save();

        if (!$uidOption) {
            Option::create([
                'name' => $this->botUserIdOption,
                'o_valuer' => $user->id,
                'o_type' => 'ai_member'
            ]);
        }

        // Handle Group Management
        if (!empty($data['managed_group_slug'])) {
            $group = \App\Models\Group::where('slug', $data['managed_group_slug'])->first();
            if ($group) {
                // Set Bot as Owner
                $group->owner_id = $user->id;
                $group->save();

                // Create or update Membership
                \App\Models\GroupMembership::updateOrCreate(
                    ['group_id' => $group->id, 'user_id' => $user->id],
                    [
                        'role' => \App\Models\GroupMembership::ROLE_OWNER,
                        'status' => \App\Models\GroupMembership::STATUS_ACTIVE,
                        'approved_at' => now(),
                    ]
                );
            }
        }
    }

    public function getBotUser()
    {
        $uidOption = Option::where('name', $this->botUserIdOption)->first();
        if ($uidOption) {
            return User::find($uidOption->o_valuer);
        }
        return null;
    }

    private function callGemini($systemPrompt, $userPrompt)
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'] ?? null;

        if (!$apiKey)
            return null;

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser Input: " . $userPrompt]
                        ]
                    ]
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $data['candidates'][0]['content']['parts'][0]['text'];
                    return trim($text);
                }
            } else {
                Log::error('AI Member API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AI Member Exception: ' . $e->getMessage());
        }

        return null;
    }

    public function runTick()
    {
        if (!$this->isEnabled())
            return [];

        $config = $this->getConfig();
        $bot = $this->getBotUser();
        if (!$bot)
            return [];

        // --- PERFORMANCE OPTIMIZATION: Probability Check ---
        $probability = (int) ($config['tick_probability'] ?? 10);
        if (rand(1, 100) > $probability) {
            return []; // Skip execution based on probability to save server load
        }

        // --- PERFORMANCE OPTIMIZATION: Global Cooldown ---
        $cooldownMinutes = (int) ($config['cooldown_minutes'] ?? 5);
        $globalCooldownKey = 'ai_member_global_cooldown';
        $lastActionTime = \Illuminate\Support\Facades\Cache::get($globalCooldownKey, 0);
        if (time() < $lastActionTime + ($cooldownMinutes * 60)) {
            return []; // Bot is resting
        }

        $actions = [];

        // Helper to mark action done and stop execution
        $markActionDone = function($actionName) use (&$actions, $globalCooldownKey, $bot) {
            $actions[] = $actionName;
            \Illuminate\Support\Facades\Cache::put($globalCooldownKey, time(), now()->addDays(30));
            $bot->online = time();
            $bot->save();
        };

        // 1. Check Auto-Posting
        $postFreqHours = (int) ($config['post_frequency_hours'] ?? 0);
        if ($postFreqHours > 0) {
            $cacheKey = 'ai_member_last_post';
            $lastPostTime = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
            $nextPostTime = $lastPostTime + ($postFreqHours * 3600);

            if (time() >= $nextPostTime) {
                if ($this->generatePost($bot, $config)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, time(), now()->addDays(30));
                    $markActionDone('post_created');
                    return $actions; // STOP EXECUTING
                }
            }
        }

        // 2. Check Messages
        if (!empty($config['enable_messages'])) {
            if ($this->processMessages($bot, $config)) {
                $markActionDone('message_replied');
                return $actions; // STOP EXECUTING
            }
        }

        // 3. Check Comments and Mentions
        if (!empty($config['enable_comments'])) {
            if ($this->processMentions($bot, $config)) {
                $markActionDone('mention_replied');
                return $actions; // STOP EXECUTING
            } elseif ($this->processComments($bot, $config)) {
                $markActionDone('comment_replied');
                return $actions; // STOP EXECUTING
            }
        }

        // 4. Random Reaction
        if (!empty($config['enable_reactions'])) {
            $cacheKey = 'ai_member_last_reaction';
            $lastReactionTime = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
            if (time() >= ($lastReactionTime + 7200)) {
                if ($this->generateReaction($bot)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, time(), now()->addDays(30));
                    $markActionDone('reaction_added');
                    return $actions; // STOP EXECUTING
                }
            }
        }

        // 5. Group Management & Moderation
        if (!empty($config['managed_group_slug'])) {
            $group = \App\Models\Group::where('slug', $config['managed_group_slug'])->first();
            if ($group) {
                // Group Moderation (Highest priority in group)
                if (!empty($config['enable_group_moderation'])) {
                    if ($this->processGroupModeration($bot, $config, $group)) {
                        $markActionDone('group_moderated');
                        return $actions; // STOP EXECUTING
                    }
                }

                // Group Auto-Posting
                $postFreq = intval($config['group_post_frequency_hours'] ?? 12);
                if ($postFreq > 0) {
                    $cacheKeyGrp = 'ai_member_last_grp_post_' . $group->id;
                    $lastGrpPostTime = \Illuminate\Support\Facades\Cache::get($cacheKeyGrp, 0);
                    if (time() >= ($lastGrpPostTime + ($postFreq * 3600))) {
                        if ($this->generateGroupPost($bot, $config, $group)) {
                            \Illuminate\Support\Facades\Cache::put($cacheKeyGrp, time(), now()->addDays(30));
                            $markActionDone('group_post_created');
                            return $actions; // STOP EXECUTING
                        }
                    }
                }

                // Group Comments
                if (!empty($config['enable_comments'])) {
                    if ($this->generateGroupComment($bot, $config, $group)) {
                        $markActionDone('group_comment_added');
                        return $actions; // STOP EXECUTING
                    }
                }
            }
        }

        return $actions;
    }

    private function generatePost($bot, $config, $forcePrompt = null)
    {
        $persona = $config['persona_prompt'] ?? 'You are a helpful member of our community.';
        $systemPrompt = $persona . " Write a short, engaging community post (max 500 characters). Do NOT include hashtags unless relevant. Do NOT use markdown formatting, just plain text.";

        if ($forcePrompt) {
            $prompt = $forcePrompt;
        } else {
            // Randomize the topic prompt slightly to avoid repetitive posts
            $topics = ['share a tip', 'ask an engaging question to the community', 'share an interesting fact', 'wish everyone a great day'];
            $prompt = "Topic to write about right now: " . $topics[array_rand($topics)];
        }

        $content = $this->callGemini($systemPrompt, $prompt);

        if ($content) {
            $time = time();

            $topic = new \App\Models\ForumTopic();
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
            return true;
        }

        return false;
    }

    private function processMessages($bot, $config)
    {
        // Find 1 unread message sent to the bot
        $message = Message::where('us_rec', $bot->id)
            ->where('state', '!=', 0)
            ->orderBy('id_msg', 'desc')
            ->first();

        if (!$message)
            return false;

        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $systemPrompt = $persona . " You are replying to a private message from another user. Be conversational and helpful. Do not use markdown.";

        if ($message->us_env == 1) {
            $systemPrompt .= " SPECIAL INSTRUCTION: The sender is the system administrator. If the administrator asks you to publish or post a status/post right now on the feed, DO NOT reply normally. Instead, you MUST reply with exactly this strict format: '[CMD_POST: topic]' where 'topic' is the specific topic they asked you to post about (or 'default' if no topic was specified). Example: [CMD_POST: football] or [CMD_POST: default].";
        }

        $replyContent = $this->callGemini($systemPrompt, $message->text);

        if ($replyContent) {
            // Mark ALL unread messages from this specific user as read
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
            $reply->state = 3; // 3 means unread
            $reply->save();

            // Send Notification
            $sender = \App\Models\User::find($message->us_env);
            if ($sender) {
                app(\App\Services\NotificationService::class)->send(
                    $sender,
                    __('messages.message_notification', ['user' => $bot->username]),
                    route('messages.show', Message::encodeConversationRouteKey($bot, $sender), false),
                    'envelope',
                    $bot->id,
                    'new_message',
                    false
                );
            }

            return true;
        }

        return false;
    }

    private function processMentions($bot, $config)
    {
        // Find recent mentions of the bot in comments
        $mentions = \App\Models\StatusMention::where('mentioned_user_id', $bot->id)
            ->where('comment_type', 'forum')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        foreach ($mentions as $mention) {
            $mentionComment = \App\Models\ForumComment::find($mention->comment_id);
            if (!$mentionComment)
                continue;

            // Check if the bot has already replied to this specific topic AFTER this mention
            $hasReplied = \App\Models\ForumComment::where('tid', $mentionComment->tid)
                ->where('uid', $bot->id)
                ->where('id', '>', $mentionComment->id)
                ->exists();

            if (!$hasReplied) {
                // The bot needs to reply!
                $topic = \App\Models\ForumTopic::find($mentionComment->tid);
                if (!$topic)
                    continue;

                $mentioner = \App\Models\User::find($mention->user_id);
                $mentionerName = $mentioner ? $mentioner->username : 'A user';

                $persona = $config['persona_prompt'] ?? 'You are a helpful member.';

                // Construct the prompt with context
                $systemPrompt = $persona . " 
You were mentioned by @{$mentionerName} in a comment on a community post. 
You must reply to them directly, and your reply must be relevant to BOTH their comment and the original post's topic. 
Do not use markdown. Keep it natural and conversational.

ORIGINAL POST TEXT:
\"" . strip_tags($topic->txt) . "\"

THEIR COMMENT MENTIONING YOU:
\"" . strip_tags($mentionComment->txt) . "\"";

                $replyContent = $this->callGemini($systemPrompt, "Please reply to the comment above.");

                if ($replyContent) {
                    $mentionText = $mentioner ? '@' . $mentioner->username . ' ' : '';

                    $reply = new \App\Models\ForumComment();
                    $reply->uid = $bot->id;
                    $reply->tid = $topic->id;
                    $reply->txt = $mentionText . $replyContent;
                    $reply->date = time();
                    $reply->save();

                    // Trigger Mentions
                    app(\App\Services\MentionService::class)->createCommentMentions(
                        $bot,
                        'forum',
                        (int) $reply->id,
                        $reply->txt,
                        "/t" . $topic->id
                    );

                    // Update the reply count on the topic
                    $topic->increment('reply');

                    return true; // Only 1 per tick
                }
            }
        }

        return false;
    }

    private function processComments($bot, $config)
    {
        // Find recent posts by the bot
        $recentStatuses = Status::where('uid', $bot->id)
            ->whereIn('s_type', [100, 4, 2])
            ->orderBy('id', 'desc')
            ->take(5)
            ->pluck('tp_id');

        if ($recentStatuses->isEmpty())
            return false;

        foreach ($recentStatuses as $tid) {
            // Find the latest comment on THIS specific post
            $latestComment = ForumComment::where('tid', $tid)
                ->orderBy('id', 'desc')
                ->first();

            // If there's a comment and it's NOT from the bot, reply to it
            if ($latestComment && $latestComment->uid != $bot->id) {
                $topic = \App\Models\ForumTopic::find($tid);
                if (!$topic)
                    continue;

                $persona = $config['persona_prompt'] ?? 'You are a helpful member.';

                $systemPrompt = $persona . " 
You are replying to a comment on your own community post. 
Your reply must be directly relevant to BOTH their comment and the context of your original post.
Do not use markdown. Keep it natural and conversational.

YOUR ORIGINAL POST TEXT:
\"" . strip_tags($topic->txt) . "\"

THEIR COMMENT TO YOU:
\"" . strip_tags($latestComment->txt) . "\"";

                $replyContent = $this->callGemini($systemPrompt, "Please reply to their comment.");

                if ($replyContent) {
                    $commentAuthor = \App\Models\User::find($latestComment->uid);
                    $mention = $commentAuthor ? '@' . $commentAuthor->username . ' ' : '';

                    $reply = new ForumComment();
                    $reply->uid = $bot->id;
                    $reply->tid = $tid;
                    $reply->txt = $mention . $replyContent;
                    $reply->date = time();
                    $reply->save();

                    // Trigger Mentions
                    app(\App\Services\MentionService::class)->createCommentMentions(
                        $bot,
                        'forum',
                        (int) $reply->id,
                        $reply->txt,
                        "/t" . $tid
                    );

                    // Update the reply count on the topic
                    $topic = \App\Models\ForumTopic::find($tid);
                    if ($topic) {
                        $topic->increment('reply');
                    }

                    return true; // Process one comment per tick to keep it natural
                }
            }
        }

        return false;
    }

    private function generateReaction($bot)
    {
        // Find a recent status not by bot
        $status = Status::where('uid', '!=', $bot->id)
            ->whereIn('s_type', [100, 4, 2, 1, 7867])
            ->orderBy('id', 'desc')
            ->first();

        if (!$status)
            return false;

        // Check if already liked
        $sid = ($status->s_type == 100 || $status->s_type == 4 || $status->s_type == 2) ? $status->tp_id : $status->id;

        // Wait, what is the ID for likes?
        // Status.php: $this->interactionSubjectId() returns $this->tp_id for standard posts.
        $sid = $status->tp_id;

        $type = 2; // For standard posts (2, 4, 100)
        if ($status->s_type == 1)
            $type = 22;
        if ($status->s_type == 7867)
            $type = 3;

        $exists = Like::where('sid', $sid)
            ->where('uid', $bot->id)
            ->where('type', $type)
            ->exists();

        if ($exists)
            return false;

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
            'o_mode' => $like->time_t
        ]);

        // Send Notification to owner
        $ownerId = null;
        $postUrl = "";

        if ($type == 2) {
            $topic = \App\Models\ForumTopic::find($sid);
            $ownerId = $topic ? $topic->uid : null;
            $postUrl = "/t" . $sid;
        } elseif ($type == 22) {
            $site = \App\Models\Directory::find($sid);
            $ownerId = $site ? $site->uid : null;
            $postUrl = "/dr" . $sid;
        }

        if ($ownerId && $ownerId != $bot->id) {
            app(\App\Services\NotificationService::class)->send(
                $ownerId,
                __('messages.reaction_notification', ['user' => $bot->username]),
                $postUrl,
                $reaction,
                $bot->id,
                'reaction'
            );
        }

        return true;
    }

    private function generateGroupPost($bot, $config, $group)
    {
        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $rules = $config['group_rules'] ?? '';

        $systemPrompt = $persona . " 
You are the owner and manager of a community group.
Write an engaging post (max 500 characters) for your group members.
Start a discussion, ask a question, or share an insight related to the group's theme.
Do NOT use markdown.
Group Rules for context: {$rules}";

        $content = $this->callGemini($systemPrompt, "Write a new post for your group.");

        if ($content) {
            $time = time();
            $topic = new \App\Models\ForumTopic();
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

            $status = new \App\Models\Status();
            $status->uid = $bot->id;
            $status->tp_id = $topic->id;
            $status->s_type = 100;
            $status->date = $time;
            $status->statu = 1;
            $status->group_id = $group->id;
            $status->save();

            $group->increment('posts_count');

            return true;
        }
        return false;
    }

    private function generateGroupComment($bot, $config, $group)
    {
        // 30% chance to comment per tick on the latest topic
        if (rand(1, 100) > 30)
            return false;

        $latestTopic = \App\Models\ForumTopic::where('group_id', $group->id)
            ->where('uid', '!=', $bot->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestTopic)
            return false;

        $hasCommented = \App\Models\ForumComment::where('tid', $latestTopic->id)
            ->where('uid', $bot->id)
            ->exists();

        if ($hasCommented)
            return false;

        $persona = $config['persona_prompt'] ?? 'You are a helpful member.';
        $systemPrompt = $persona . " 
You are reading a post in your community group.
Write a friendly, engaging comment in response to the post below.
Do not use markdown.

POST TEXT:
\"" . strip_tags($latestTopic->txt) . "\"";

        $replyContent = $this->callGemini($systemPrompt, "Please write a comment.");

        if ($replyContent) {
            $reply = new \App\Models\ForumComment();
            $reply->uid = $bot->id;
            $reply->tid = $latestTopic->id;
            $reply->txt = $replyContent;
            $reply->date = time();
            $reply->save();

            $latestTopic->increment('reply');

            app(\App\Services\NotificationService::class)->send(
                $latestTopic->uid,
                __('messages.comment_notification', ['user' => $bot->username]),
                "/t" . $latestTopic->id,
                'logo',
                $bot->id,
                'forum_reply'
            );

            return true;
        }
        return false;
    }

    private function processGroupModeration($bot, $config, $group)
    {
        $rules = $config['group_rules'] ?? '';
        if (empty($rules))
            return false;

        $cacheKey = 'ai_member_last_mod_topic_' . $group->id;
        $lastTopicId = Cache::get($cacheKey, 0);

        $topic = \App\Models\ForumTopic::where('group_id', $group->id)
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
        $lastComId = Cache::get($cacheKeyCom, 0);

        $topicIds = \App\Models\ForumTopic::where('group_id', $group->id)->pluck('id');
        if ($topicIds->isEmpty())
            return false;

        $comment = \App\Models\ForumComment::whereIn('tid', $topicIds)
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

    private function moderateContent($bot, $group, $rules, $contentObj, $type)
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
                if ($type == 'topic') {
                    $contentObj->delete();
                    \App\Models\Status::where('tp_id', $contentObj->id)->where('s_type', 100)->delete();
                } else {
                    $contentObj->delete();
                }
                $this->notifyUserOfModeration($bot, $userId, $group, $reason, false);
            } elseif (preg_match('/\[MOD_KICK\](.*)/i', $response, $matches)) {
                $reason = trim($matches[1] ?? 'Severe violation of group rules');
                if ($type == 'topic') {
                    $contentObj->delete();
                    \App\Models\Status::where('tp_id', $contentObj->id)->where('s_type', 100)->delete();
                } else {
                    $contentObj->delete();
                }
                
                \App\Models\GroupMembership::where('group_id', $group->id)
                    ->where('user_id', $userId)
                    ->delete();
                    
                $this->notifyUserOfModeration($bot, $userId, $group, $reason, true);
            }
        }
    }

    private function notifyUserOfModeration($bot, $userId, $group, $reason, $isKick)
    {
        $groupName = $group->name;

        $pmTitle = __('ai_member::messages.mod_pm_title');
        $pmBodyKey = $isKick ? 'ai_member::messages.mod_pm_kick_body' : 'ai_member::messages.mod_pm_delete_body';
        $pmBody = __($pmBodyKey, ['group' => $groupName, 'reason' => $reason]);
        $pmFooter = __('ai_member::messages.mod_pm_footer');
        
        $pmText = "{$pmTitle}\n\n{$pmBody}\n\n{$pmFooter}";

        $message = new \App\Models\Message();
        $message->name = $bot->username ?? 'AI Member';
        $message->us_rec = $userId;
        $message->us_env = $bot->id;
        $message->text = $pmText;
        $message->state = 3;
        $message->time = time();
        $message->save();

        $notifKey = $isKick ? 'ai_member::messages.mod_notif_kick' : 'ai_member::messages.mod_notif_delete';
        $notifText = __($notifKey, ['group' => $groupName]);

        app(\App\Services\NotificationService::class)->send(
            $userId,
            $notifText,
            "/groups/" . $group->slug,
            'logo',
            $bot->id,
            'system'
        );
    }
}
