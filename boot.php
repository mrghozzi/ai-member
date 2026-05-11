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
    function ai_member_service()
    {
        return new AiMemberService();
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
        return view('ai_member::admin.settings', [
            'config' => $service->getConfig(),
            'botUser' => $service->getBotUser()
        ]);
    })->name('admin.ai-member.index');

    Route::post('/admin/ai-member/save', function (Request $request) {
        $service = ai_member_service();
        $service->saveConfig($request->all());
        
        // Also handle bot user creation/update
        $service->syncBotUser($request->all());

        return redirect()->back()->with('success', __('ai_member::messages.success_save'));
    })->name('admin.ai-member.save');
});

// Member API Route (The Background Tick)
Route::middleware(['web'])->group(function () {
    Route::post('/api/ai-member/tick', function (Request $request) {
        // Disable execution if the request is not AJAX or if it's hitting a rate limit
        $service = ai_member_service();
        
        if (!$service->isEnabled()) {
            return response()->json(['status' => 'disabled']);
        }

        // Run the tick (this checks frequencies and performs actions if needed)
        try {
            $actionsPerformed = $service->runTick();
            return response()->json(['status' => 'ok', 'actions' => $actionsPerformed]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Member Tick Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    })->name('ai-member.tick');
});

// Admin Sidebar
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

// Inject Tick JS
Hooks::add_action('theme_master_before_body_close', function (): void {
    // Inject only if enabled
    $config = ai_member_service()->getConfig();
    if (!empty($config['is_enabled'])) {
        // Random chance to actually send the tick to reduce server load (e.g., 20% chance per page view)
        $chance = 20; // 20%
        if (rand(1, 100) <= $chance) {
            echo '<script>
                setTimeout(function() {
                    fetch("' . route('ai-member.tick') . '", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector("meta[name=\'csrf-token\']").getAttribute("content")
                        }
                    }).catch(err => console.log("AI Tick skipped"));
                }, 5000); // Wait 5 seconds after page load
            </script>';
        }
    }
});
