<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Helpers\Hooks;
use MyAds\Plugins\AiMember\AiMemberService;

// Ensure Service is loaded
if (!class_exists('MyAds\Plugins\AiMember\AiMemberService')) {
    require_once __DIR__ . '/src/AiMemberService.php';
}

if (!function_exists('ai_member_service')) {
    function ai_member_service(): AiMemberService
    {
        static $instance = null;
        if (!$instance instanceof AiMemberService) {
            $instance = new AiMemberService();
        }
        return $instance;
    }
}

// Load Translations
app('translator')->addNamespace('ai_member', __DIR__ . '/lang');

// Load Views
View::addNamespace('ai_member', __DIR__ . '/views');

// Admin Routes
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/ai-member', function () {
        $service = ai_member_service();
        $botUser = $service->getBotUser();

        return view('ai_member::admin.settings', [
            'config' => $service->getConfig(),
            'botUser' => $botUser,
            'pendingTasks' => $service->getPendingTasks(),
            'activityLogs' => $service->getActivityLogs(),
            'cooldownRemaining' => $service->getCooldownRemaining(),
            'nextTickTime' => $service->getNextTickTime(),
        ]);
    })->name('admin.ai-member.index');

    Route::post('/admin/ai-member/save', function (Request $request) {
        $service = ai_member_service();
        $service->saveConfig($request->all());
        $service->syncBotUser($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ai_member::messages.success_save'),
            ]);
        }

        return redirect()->back()->with('success', __('ai_member::messages.success_save'));
    })->name('admin.ai-member.save');

    Route::post('/admin/ai-member/cancel-task', function (Request $request) {
        $service = ai_member_service();
        $taskKey = (string) $request->input('task_key', '');
        $success = $service->cancelTask($taskKey);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => __('ai_member::messages.task_cancelled'),
                'tasks_html' => view('ai_member::admin.partials.tasks', [
                    'pendingTasks' => $service->getPendingTasks(),
                ])->render(),
            ]);
        }

        return redirect()->back()->with('success', __('ai_member::messages.task_cancelled'));
    })->name('admin.ai-member.cancel-task');

    Route::post('/admin/ai-member/execute-task', function (Request $request) {
        $service = ai_member_service();
        $taskKey = (string) $request->input('task_key', '');

        try {
            $success = $service->executeTask($taskKey);
            $message = $success
                ? __('ai_member::messages.task_execute_success')
                : __('ai_member::messages.task_execute_failed');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => $success,
                    'message' => $message,
                    'cooldown_remaining' => $service->getCooldownRemaining(),
                    'tasks_html' => view('ai_member::admin.partials.tasks', [
                        'pendingTasks' => $service->getPendingTasks(),
                    ])->render(),
                ]);
            }

            return redirect()->back()->with($success ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Member Execute Task Error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    })->name('admin.ai-member.execute-task');

    Route::get('/admin/ai-member/list-models', function (Request $request) {
        $apiKey = $request->query('api_key');
        $result = ai_member_service()->listAvailableModels($apiKey);
        $status = !empty($result['success']) ? 200 : (!empty($result['error']) && stripos($result['error'], 'HTTP 4') === 0 ? 400 : 500);
        return response()->json($result, $status);
    })->name('admin.ai-member.list-models');

    Route::post('/admin/ai-member/test-ai', function (Request $request) {
        $service = ai_member_service();
        $prompt = (string) $request->input('prompt', '');
        $result = $service->testAi($prompt !== '' ? $prompt : 'Hello! Tell me an inspiring thought.');
        return response()->json($result);
    })->name('admin.ai-member.test-ai');

    Route::post('/admin/ai-member/reset-cooldown', function () {
        $service = ai_member_service();
        $service->resetCooldown();

        return response()->json([
            'success' => true,
            'message' => __('ai_member::messages.cooldown_reset_success'),
            'cooldown_remaining' => 0,
        ]);
    })->name('admin.ai-member.reset-cooldown');

    Route::post('/admin/ai-member/clear-logs', function () {
        $service = ai_member_service();
        $service->clearActivityLogs();

        return response()->json([
            'success' => true,
            'message' => __('ai_member::messages.logs_cleared'),
        ]);
    })->name('admin.ai-member.clear-logs');
});

// Member API Route (The Background Tick with Rate Limiting)
Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::post('/api/ai-member/tick', function (Request $request) {
        $service = ai_member_service();

        if (!$service->isEnabled()) {
            return response()->json(['status' => 'disabled']);
        }

        try {
            $actionsPerformed = $service->runTick();
            return response()->json(['status' => 'ok', 'actions' => $actionsPerformed]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Member Tick Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    })->name('ai-member.tick');
});

// Admin Sidebar Menu Hook
Hooks::add_action('admin_sidebar_menu', function (): void {
    $url = route('admin.ai-member.index');
    $isActive = request()->is('admin/ai-member*');
    $linkClass = $isActive ? 'nxl-link active' : 'nxl-link';

    echo '<li class="nxl-item">'
        . '<a href="' . e($url) . '" class="' . e($linkClass) . '">'
        . '<span class="nxl-micon"><i class="feather-cpu"></i></span>'
        . '<span class="nxl-mtext">' . e(__('ai_member::messages.sidebar_menu')) . '</span>'
        . '</a>'
        . '</li>';
});

// Inject Tick JS (Ultra-lightweight, only when server is ready)
Hooks::add_action('theme_master_before_body_close', function (): void {
    $service = ai_member_service();
    $config = $service->getConfig();

    if (!empty($config['is_enabled'])) {
        // Skip script injection completely if bot is on cooldown
        if ($service->getCooldownRemaining() > 0) {
            return;
        }

        // 5% gentle chance per page view
        if (rand(1, 100) <= 5) {
            $tickUrl = route('ai-member.tick');
            echo '<script>
                setTimeout(function() {
                    try {
                        var lastTick = localStorage.getItem("ai_member_last_tick");
                        var now = Date.now();
                        if (lastTick && (now - parseInt(lastTick)) < 600000) return; // 10 minutes interval per browser
                        localStorage.setItem("ai_member_last_tick", now);

                        var csrfMeta = document.querySelector(\'meta[name="csrf-token"]\');
                        if (!csrfMeta) return;

                        fetch("' . $tickUrl . '", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrfMeta.getAttribute("content"),
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        }).catch(function() {});
                    } catch (e) {}
                }, 15000); // Wait 15 seconds after page load
            </script>';
        }
    }
});
