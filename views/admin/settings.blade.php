@extends('admin::layouts.admin')

@section('title', __('ai_member::messages.settings_title'))
@section('admin_shell_header_mode', 'hidden')

@section('content')
<div class="ai-member-page">
    <!-- Superdesign Header -->
    <div class="row g-0 align-items-center mb-4">
        <div class="col-12 px-3 px-md-4">
            <div class="card border-0 shadow-lg overflow-hidden position-relative hero-card">
                <!-- Decorative Elements -->
                <div class="position-absolute top-0 end-0 p-5 opacity-10 d-none d-md-block pointer-events-none">
                    <i class="feather-cpu" style="font-size: 180px; transform: rotate(-15deg);"></i>
                </div>
                
                <div class="card-body p-4 p-xl-5 position-relative z-index-1">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-7 text-white">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                                    <i class="feather-cpu me-1"></i> AI Member
                                </span>
                                <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-2 fw-bold">
                                    v1.4.0
                                </span>
                                @if(!empty($config['is_enabled']))
                                    <span class="badge bg-success text-white rounded-pill px-3 py-2 fw-bold">
                                        <i class="feather-check-circle me-1"></i> {{ __('ai_member::messages.status_active') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger text-white rounded-pill px-3 py-2 fw-bold">
                                        <i class="feather-x-circle me-1"></i> {{ __('ai_member::messages.status_inactive') }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="display-6 fw-black text-white mb-2">
                                {{ __('ai_member::messages.hero_title') }}
                            </h1>
                            <p class="lead opacity-80 mb-0 fs-15 text-white">
                                {{ __('ai_member::messages.hero_copy') }}
                            </p>
                        </div>
                        
                        <div class="col-lg-5 text-lg-end">
                            <div class="card border-0 shadow-sm glass-stat-card">
                                <div class="card-body p-4 text-start">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-text avatar-lg rounded-circle text-white bg-white bg-opacity-20">
                                                @if(!empty($botUser && $botUser->img))
                                                    <img src="{{ $botUser->img }}" alt="Bot Avatar" class="rounded-circle w-100 h-100 object-fit-cover">
                                                @else
                                                    <i class="feather-user"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <h5 class="text-white fw-bold mb-0">{{ $botUser->username ?? ($config['bot_name'] ?? 'AI Member') }}</h5>
                                                <small class="text-white opacity-80 d-block">{{ '@' . ($botUser->name ?? ($config['bot_username'] ?? 'ai_member')) }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-1 fs-11">
                                                {{ $config['api_model'] ?? 'gemini-flash-latest' }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Cooldown Live Status Bar -->
                                    <div class="pt-2 border-top border-white border-opacity-20 d-flex align-items-center justify-content-between">
                                        <div id="cooldownStatusText" class="text-white fs-12">
                                            @if(($cooldownRemaining ?? 0) > 0)
                                                <i class="feather-clock text-warning me-1"></i>
                                                <span>{{ __('ai_member::messages.remaining_cooldown') }} <strong id="cooldownSecondsText">{{ $cooldownRemaining }}s</strong></span>
                                            @else
                                                <i class="feather-check-circle text-success me-1"></i>
                                                <span>{{ __('ai_member::messages.bot_ready') }}</span>
                                            @endif
                                        </div>
                                        <button type="button" id="btn-reset-cooldown" class="btn btn-sm btn-white text-dark rounded-pill fw-bold px-3 py-1 hover-scale-11" title="{{ __('ai_member::messages.reset_cooldown_button') }}">
                                            <i class="feather-rotate-cw me-1"></i> {{ __('ai_member::messages.reset_cooldown_button') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AJAX Alert Notifications Container -->
    <div class="container-fluid px-3 px-md-4">
        <div id="ajaxAlertsContainer"></div>
    </div>

    @if(session('success'))
    <div class="container-fluid px-3 px-md-4 mb-4">
        <div class="alert alert-success shadow-sm border-0 d-flex align-items-center" style="border-radius: 16px;">
            <i class="feather-check-circle fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="container-fluid px-3 px-md-4 mb-4">
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" style="border-radius: 16px;">
            <i class="feather-alert-triangle fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    </div>
    @endif

    <div class="main-content container-fluid px-3 px-md-4">
        <div class="row g-4">
            <!-- Left / Main Column: Settings & Configuration -->
            <div class="col-xl-8">
                <!-- AI Verification Sandbox Card -->
                <div class="card border-0 shadow-sm mb-4 section-card">
                    <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-text avatar-sm bg-soft-primary text-primary rounded-circle">
                                <i class="feather-terminal"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ __('ai_member::messages.test_ai_title') }}</h5>
                                <small class="text-muted">{{ __('ai_member::messages.test_ai_desc') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="input-group input-group-lg border-soft-light bg-light rounded-4 overflow-hidden mb-3">
                            <input type="text" id="test-prompt" class="form-control bg-transparent border-0 shadow-none ps-3 fs-14" placeholder="{{ __('ai_member::messages.test_ai_placeholder') }}">
                            <button type="button" id="btn-test-ai" class="btn btn-primary px-4 fw-bold">
                                <i class="feather-play me-1"></i> {{ __('ai_member::messages.test_ai_button') }}
                            </button>
                        </div>
                        
                        <div id="test-result-wrapper" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-bold small text-muted mb-0">{{ __('ai_member::messages.test_ai_result') }}</label>
                                <span id="test-latency-badge" class="badge bg-light text-dark border px-2 py-1"></span>
                            </div>
                            <div id="test-result" class="p-3 bg-light rounded-3 border small" style="min-height: 50px; white-space: pre-wrap;"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Settings Form -->
                <div class="card border-0 shadow-sm mb-4 section-card">
                    <div class="card-header border-0 bg-transparent pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-1 text-dark">{{ __('ai_member::messages.config_title') }}</h5>
                        <p class="text-muted mb-0 fs-13">{{ __('ai_member::messages.config_desc') }}</p>
                    </div>
                    <div class="card-body p-4 pt-2">
                        <form id="aiMemberSettingsForm" action="{{ route('admin.ai-member.save') }}" method="POST">
                            @csrf
                            
                            <!-- Master Switch -->
                            <div class="card border border-soft-light shadow-none mb-4 switch-panel">
                                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-check-label fw-bold fs-15 text-dark mb-1" for="is_enabled">
                                            {{ __('ai_member::messages.is_enabled_label') }}
                                        </label>
                                        <div class="text-muted small">
                                            {{ !empty($config['is_enabled']) ? __('ai_member::messages.status_active_desc') : __('ai_member::messages.status_inactive_desc') }}
                                        </div>
                                    </div>
                                    <div class="form-check form-switch form-switch-lg mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" value="1" {{ !empty($config['is_enabled']) ? 'checked' : '' }} style="width: 50px; height: 26px;">
                                    </div>
                                </div>
                            </div>

                            <!-- API Key -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.api_key_label') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-3"><i class="feather-key"></i></span>
                                    <input type="password" name="api_key" id="ai-member-api-key" class="form-control bg-transparent border-0 shadow-none ps-2" value="{{ $config['api_key'] ?? '' }}" placeholder="{{ __('ai_member::messages.api_key_placeholder') }}">
                                    <button type="button" id="btn-toggle-key-visibility" class="btn btn-outline-secondary border-0 text-muted" title="Show/Hide">
                                        <i class="feather-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.api_key_help') }}</small>
                            </div>

                            <!-- Model Selector -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.api_model_label') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                    <select name="api_model" id="ai-member-api-model" class="form-select bg-transparent border-0 shadow-none ps-3">
                                        <option value="gemini-flash-latest" {{ ($config['api_model'] ?? 'gemini-flash-latest') === 'gemini-flash-latest' ? 'selected' : '' }}>Gemini Flash (Latest Stable - Recommended)</option>
                                        <option value="gemini-2.5-flash" {{ ($config['api_model'] ?? '') === 'gemini-2.5-flash' ? 'selected' : '' }}>Gemini 2.5 Flash</option>
                                        <option value="gemini-2.5-pro" {{ ($config['api_model'] ?? '') === 'gemini-2.5-pro' ? 'selected' : '' }}>Gemini 2.5 Pro</option>
                                        <option value="gemini-2.0-flash" {{ ($config['api_model'] ?? '') === 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash</option>
                                        <option value="gemini-pro-latest" {{ ($config['api_model'] ?? '') === 'gemini-pro-latest' ? 'selected' : '' }}>Gemini Pro (Latest)</option>
                                    </select>
                                    <button type="button" id="btn-load-models" class="btn btn-outline-primary border-0" title="{{ __('ai_member::messages.load_models_button') }}">
                                        <i class="feather-refresh-cw"></i>
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.api_model_help') }}</small>
                                <div id="models-status" class="small mt-1"></div>
                            </div>

                            <!-- Bot Identity Section -->
                            <div class="position-relative my-4">
                                <hr class="border-soft-light">
                                <div class="position-absolute top-50 start-50 translate-middle px-3 section-divider-badge">
                                    <h6 class="fw-bold text-muted mb-0"><i class="feather-user me-2 text-primary"></i>Bot Profile & Identity</h6>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_name_label') }}</label>
                                    <input type="text" name="bot_name" class="form-control form-control-lg border-soft-light bg-light rounded-3" value="{{ $config['bot_name'] ?? 'AI Member' }}" placeholder="{{ __('ai_member::messages.bot_name_placeholder') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_username_label') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <span class="input-group-text bg-transparent border-0 text-muted ps-3">@</span>
                                        <input type="text" name="bot_username" class="form-control bg-transparent border-0 shadow-none ps-2" value="{{ $config['bot_username'] ?? 'ai_member' }}" placeholder="{{ __('ai_member::messages.bot_username_placeholder') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_avatar_label') }}</label>
                                <input type="url" name="bot_avatar" class="form-control form-control-lg border-soft-light bg-light rounded-3" value="{{ $config['bot_avatar'] ?? asset('upload/avatar.png') }}" placeholder="{{ __('ai_member::messages.bot_avatar_placeholder') }}">
                            </div>

                            <!-- AI Persona & Behavior Section -->
                            <div class="position-relative my-4">
                                <hr class="border-soft-light">
                                <div class="position-absolute top-50 start-50 translate-middle px-3 section-divider-badge">
                                    <h6 class="fw-bold text-muted mb-0"><i class="feather-cpu me-2 text-primary"></i>AI Persona & Actions</h6>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.persona_label') }}</label>
                                <textarea name="persona_prompt" class="form-control border-soft-light bg-light p-3 rounded-3" rows="3" placeholder="{{ __('ai_member::messages.persona_placeholder') }}">{{ $config['persona_prompt'] ?? 'You are a helpful and friendly AI assistant member of our social community. You like to share interesting facts about technology, science, and life. Keep your posts and replies short, engaging, and in the language the user speaks to you in (Arabic or English).' }}</textarea>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.post_frequency_label') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <input type="number" name="post_frequency_hours" class="form-control bg-transparent border-0 shadow-none ps-3" value="{{ $config['post_frequency_hours'] ?? '24' }}" min="0">
                                        <span class="input-group-text bg-transparent border-0 text-muted pe-3">Hours</span>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.post_frequency_help') }}</small>
                                </div>
                            </div>

                            <!-- Activity Feature Switches -->
                            <div class="card border border-soft-light shadow-none mb-4 switch-panel">
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" name="enable_messages" id="enable_messages" value="1" {{ !empty($config['enable_messages']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark ms-2" for="enable_messages">
                                                    {{ __('ai_member::messages.enable_messages_label') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" name="enable_comments" id="enable_comments" value="1" {{ !empty($config['enable_comments']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark ms-2" for="enable_comments">
                                                    {{ __('ai_member::messages.enable_comments_label') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" name="enable_reactions" id="enable_reactions" value="1" {{ !empty($config['enable_reactions']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark ms-2" for="enable_reactions">
                                                    {{ __('ai_member::messages.enable_reactions_label') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border border-soft-light shadow-none mb-4 switch-panel">
                                <div class="card-body p-4">
                                    <div class="form-check form-switch d-flex align-items-center mb-0">
                                        <input class="form-check-input mt-0" type="checkbox" name="enable_auto_block" id="enable_auto_block" value="1" {{ !empty($config['enable_auto_block']) ? 'checked' : '' }} style="width: 44px; height: 22px;">
                                        <div class="ms-3">
                                            <label class="form-check-label fw-bold text-dark mb-1" for="enable_auto_block">{{ __('ai_member::messages.enable_auto_block_label') }}</label>
                                            <div class="text-muted small">{{ __('ai_member::messages.enable_auto_block_help') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- AI Image Generation Section -->
                            <div class="position-relative my-4">
                                <hr class="border-soft-light">
                                <div class="position-absolute top-50 start-50 translate-middle px-3 section-divider-badge">
                                    <h6 class="fw-bold text-muted mb-0"><i class="feather-image me-2 text-primary"></i>{{ __('ai_member::messages.image_generation_title') }}</h6>
                                </div>
                            </div>

                            <div class="card border border-soft-light shadow-none mb-4 switch-panel">
                                <div class="card-body p-4">
                                    <div class="form-check form-switch d-flex align-items-center mb-0">
                                        <input class="form-check-input mt-0" type="checkbox" name="enable_image_posts" id="enable_image_posts" value="1" {{ !empty($config['enable_image_posts']) ? 'checked' : '' }} style="width: 44px; height: 22px;">
                                        <div class="ms-3">
                                            <label class="form-check-label fw-bold text-dark mb-1" for="enable_image_posts">{{ __('ai_member::messages.enable_image_posts_label') }}</label>
                                            <div class="text-muted small">{{ __('ai_member::messages.enable_image_posts_help') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.image_model_label') }}</label>
                                    <input type="text" name="image_model" class="form-control form-control-lg border-soft-light bg-light rounded-3" value="{{ $config['image_model'] ?? 'gemini-2.0-flash-exp' }}" placeholder="gemini-2.0-flash-exp">
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.image_model_help') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.image_post_chance_label') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <input type="number" name="image_post_chance" class="form-control bg-transparent border-0 shadow-none ps-3" value="{{ $config['image_post_chance'] ?? '20' }}" min="1" max="100">
                                        <span class="input-group-text bg-transparent border-0 text-muted pe-3">%</span>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.image_post_chance_help') }}</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.image_prompt_style_label') }}</label>
                                <textarea name="image_prompt_style" class="form-control border-soft-light bg-light p-3 rounded-3" rows="2" placeholder="{{ __('ai_member::messages.image_prompt_style_placeholder') }}">{{ $config['image_prompt_style'] ?? '' }}</textarea>
                            </div>

                            <!-- Performance & Rate Limit Section -->
                            <div class="position-relative my-4">
                                <hr class="border-soft-light">
                                <div class="position-absolute top-50 start-50 translate-middle px-3 section-divider-badge">
                                    <h6 class="fw-bold text-muted mb-0"><i class="feather-server me-2 text-primary"></i>{{ __('ai_member::messages.server_performance_title') }}</h6>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.tick_probability') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <input type="number" name="tick_probability" class="form-control bg-transparent border-0 shadow-none ps-3" value="{{ $config['tick_probability'] ?? '10' }}" min="1" max="100">
                                        <span class="input-group-text bg-transparent border-0 text-muted pe-3">%</span>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.tick_probability_help') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.cooldown_minutes') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <input type="number" name="cooldown_minutes" class="form-control bg-transparent border-0 shadow-none ps-3" value="{{ $config['cooldown_minutes'] ?? '5' }}" min="0">
                                        <span class="input-group-text bg-transparent border-0 text-muted pe-3">Min</span>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.cooldown_minutes_help') }}</small>
                                </div>
                            </div>

                            <!-- Group Management Section -->
                            <div class="position-relative my-4">
                                <hr class="border-soft-light">
                                <div class="position-absolute top-50 start-50 translate-middle px-3 section-divider-badge">
                                    <h6 class="fw-bold text-muted mb-0"><i class="feather-users me-2 text-primary"></i>Group Management</h6>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_managed_slug') }}</label>
                                    <input type="text" name="managed_group_slug" class="form-control form-control-lg border-soft-light bg-light rounded-3" value="{{ $config['managed_group_slug'] ?? '' }}" placeholder="{{ __('ai_member::messages.group_managed_slug_placeholder') }}">
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.group_managed_slug_help') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_post_frequency') }}</label>
                                    <div class="input-group input-group-lg border-soft-light bg-light rounded-3 overflow-hidden">
                                        <input type="number" name="group_post_frequency_hours" class="form-control bg-transparent border-0 shadow-none ps-3" value="{{ $config['group_post_frequency_hours'] ?? '12' }}" min="0">
                                        <span class="input-group-text bg-transparent border-0 text-muted pe-3">Hours</span>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.group_post_frequency_help') }}</small>
                                </div>
                            </div>

                            <div class="card border border-soft-light shadow-none mb-4 switch-panel">
                                <div class="card-body p-4">
                                    <div class="form-check form-switch d-flex align-items-center mb-0">
                                        <input class="form-check-input mt-0" type="checkbox" name="enable_group_moderation" id="enable_group_moderation" value="1" {{ !empty($config['enable_group_moderation']) ? 'checked' : '' }} style="width: 44px; height: 22px;">
                                        <div class="ms-3">
                                            <label class="form-check-label fw-bold text-dark mb-1" for="enable_group_moderation">{{ __('ai_member::messages.group_moderation') }}</label>
                                            <div class="text-muted small">{{ __('ai_member::messages.group_moderation_help') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_rules') }}</label>
                                <textarea name="group_rules" class="form-control border-soft-light bg-light p-3 rounded-3" rows="3" placeholder="{{ __('ai_member::messages.group_rules_placeholder') }}">{{ $config['group_rules'] ?? '' }}</textarea>
                            </div>

                            <div class="text-end border-top border-soft-light pt-4 mt-4">
                                <button type="submit" id="btn-save-settings" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm hover-scale rounded-pill">
                                    <i class="feather-save me-2"></i> {{ __('ai_member::messages.save_settings') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Prioritized Task Queue & Activity Log -->
            <div class="col-xl-4">
                <!-- Prioritized Task Queue Card -->
                <div class="card border-0 shadow-sm mb-4 section-card">
                    <div class="card-header border-0 bg-transparent pt-4 pb-2 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">{{ __('ai_member::messages.pending_tasks_title') }}</h5>
                            <small class="text-muted">{{ __('ai_member::messages.pending_tasks_desc') }}</small>
                        </div>
                    </div>
                    <div class="card-body p-0" id="tasksTableContainer">
                        @include('ai_member::admin.partials.tasks', ['pendingTasks' => $pendingTasks])
                    </div>
                </div>

                <!-- Recent Activity Log Card -->
                <div class="card border-0 shadow-sm mb-4 section-card">
                    <div class="card-header border-0 bg-transparent pt-4 pb-2 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">{{ __('ai_member::messages.activity_logs_title') }}</h5>
                            <small class="text-muted">{{ __('ai_member::messages.activity_logs_desc') }}</small>
                        </div>
                        <button type="button" id="btn-clear-logs" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3 py-1 fs-12">
                            <i class="feather-trash-2 me-1"></i> {{ __('ai_member::messages.clear_logs_button') }}
                        </button>
                    </div>
                    <div class="card-body p-4 pt-2">
                        <div id="activityLogsContainer" class="activity-timeline">
                            @forelse($activityLogs as $log)
                                <div class="activity-item d-flex gap-3 mb-3 pb-3 border-bottom border-soft-light">
                                    <div class="activity-icon-badge mt-1">
                                        <i class="feather-check text-success"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark fs-13">{{ $log['description'] ?? '' }}</div>
                                        <small class="text-muted fs-11">
                                            <i class="feather-clock me-1"></i> {{ $log['formatted_time'] ?? date('Y-m-d H:i:s') }}
                                        </small>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted small" id="noLogsNotice">
                                    <i class="feather-activity fs-3 mb-2 d-block opacity-50"></i>
                                    {{ __('ai_member::messages.no_activity_logs') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const alertsContainer = document.getElementById('ajaxAlertsContainer');

    function showAjaxAlert(type, message) {
        if (!alertsContainer) return;
        const isSuccess = type === 'success';
        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-' + (isSuccess ? 'success' : 'danger') + ' shadow-sm border-0 d-flex align-items-center mb-3 animate__animated animate__fadeInDown';
        alertEl.style.borderRadius = '16px';
        alertEl.innerHTML = '<i class="feather-' + (isSuccess ? 'check-circle' : 'alert-triangle') + ' fs-4 me-3"></i><div class="fw-bold">' + message + '</div>';
        
        alertsContainer.innerHTML = '';
        alertsContainer.appendChild(alertEl);

        setTimeout(function () {
            alertEl.classList.remove('animate__fadeInDown');
            alertEl.classList.add('animate__fadeOutUp');
            setTimeout(function () { alertEl.remove(); }, 500);
        }, 4500);
    }

    // Toggle API Key Visibility
    const toggleKeyBtn = document.getElementById('btn-toggle-key-visibility');
    const apiKeyInput = document.getElementById('ai-member-api-key');
    if (toggleKeyBtn && apiKeyInput) {
        toggleKeyBtn.addEventListener('click', function () {
            const isPassword = apiKeyInput.type === 'password';
            apiKeyInput.type = isPassword ? 'text' : 'password';
            this.innerHTML = '<i class="feather-' + (isPassword ? 'eye-off' : 'eye') + '"></i>';
        });
    }

    // AJAX Settings Form Submission
    const settingsForm = document.getElementById('aiMemberSettingsForm');
    const saveBtn = document.getElementById('btn-save-settings');
    if (settingsForm && saveBtn) {
        settingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const originalHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';

            const formData = new FormData(settingsForm);

            fetch(settingsForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
                showAjaxAlert(data.success ? 'success' : 'error', data.message || '{{ __("ai_member::messages.ajax_saved_success") }}');
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
                showAjaxAlert('error', '{{ __("ai_member::messages.ajax_error") }}');
            });
        });
    }

    // Live AI Verification Sandbox
    const testAiBtn = document.getElementById('btn-test-ai');
    const testPromptInput = document.getElementById('test-prompt');
    const testResultWrapper = document.getElementById('test-result-wrapper');
    const testResultDiv = document.getElementById('test-result');
    const testLatencyBadge = document.getElementById('test-latency-badge');

    if (testAiBtn && testPromptInput) {
        testAiBtn.addEventListener('click', function () {
            const prompt = testPromptInput.value.trim();
            const originalHtml = testAiBtn.innerHTML;

            testAiBtn.disabled = true;
            testAiBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("ai_member::messages.test_ai_testing") }}';
            testResultWrapper.style.display = 'block';
            testResultDiv.className = 'p-3 bg-light rounded-3 border small';
            testResultDiv.innerHTML = '<span class="text-muted"><i class="feather-loader me-1 spin"></i> Connecting to Gemini API...</span>';

            fetch("{{ route('admin.ai-member.test-ai') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ prompt: prompt })
            })
            .then(res => res.json())
            .then(data => {
                testAiBtn.disabled = false;
                testAiBtn.innerHTML = originalHtml;

                if (data.latency_ms !== undefined) {
                    testLatencyBadge.textContent = '{{ __("ai_member::messages.test_ai_latency") }} ' + data.latency_ms + 'ms';
                }

                if (data.success) {
                    testResultDiv.className = 'p-3 bg-success bg-opacity-10 text-success rounded-3 border border-success border-opacity-25 small';
                    testResultDiv.innerHTML = '<div class="fw-bold mb-1"><i class="feather-check-circle me-1"></i> {{ __("ai_member::messages.test_ai_verified") }} <code>' + (data.model || 'Gemini') + '</code></div>' + data.response;
                } else {
                    testResultDiv.className = 'p-3 bg-danger bg-opacity-10 text-danger rounded-3 border border-danger border-opacity-25 small';
                    testResultDiv.innerHTML = '<div class="fw-bold mb-1"><i class="feather-alert-triangle me-1"></i> {{ __("ai_member::messages.test_ai_failed") }}</div>' + (data.response || 'Unknown error occurred.');
                }
            })
            .catch(err => {
                testAiBtn.disabled = false;
                testAiBtn.innerHTML = originalHtml;
                testResultDiv.className = 'p-3 bg-danger bg-opacity-10 text-danger rounded-3 border border-danger border-opacity-25 small';
                testResultDiv.innerHTML = '<div class="fw-bold mb-1"><i class="feather-alert-triangle me-1"></i> Network error:</div>' + err.message;
            });
        });
    }

    // Dynamic Model Fetcher
    const loadModelsBtn = document.getElementById('btn-load-models');
    if (loadModelsBtn) {
        loadModelsBtn.addEventListener('click', function () {
            const btn = this;
            const status = document.getElementById('models-status');
            const select = document.getElementById('ai-member-api-model');
            const apiKeyInput = document.getElementById('ai-member-api-key');
            const currentValue = select ? select.value : '';
            const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            status.innerHTML = '<span class="text-muted"><i class="feather-loader me-1 spin"></i> Fetching available Google Gemini models...</span>';

            const url = new URL("{{ route('admin.ai-member.list-models') }}", window.location.origin);
            if (apiKey) url.searchParams.set('api_key', apiKey);

            fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json().then(data => ({ status: res.status, data })))
            .then(({ data }) => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;

                if (!data.success) {
                    status.innerHTML = '<span class="text-danger"><i class="feather-alert-circle me-1"></i> ' + (data.error || 'Failed to load models') + '</span>';
                    return;
                }

                const models = data.models || [];
                if (models.length === 0) {
                    status.innerHTML = '<span class="text-warning">No text-generation models returned for this key.</span>';
                    return;
                }

                const flashFirst = models.find(m => /flash-latest|2\.5-flash|2\.0-flash/i.test(m.id));
                const proFirst = models.find(m => /pro-latest|2\.5-pro/i.test(m.id));
                const recommended = flashFirst || proFirst || models[0];

                select.innerHTML = '';
                models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.displayName + (m.id === recommended.id ? ' (Recommended)' : '');
                    if (m.id === currentValue) opt.selected = true;
                    select.appendChild(opt);
                });

                if (!models.find(m => m.id === currentValue) && recommended) {
                    select.value = recommended.id;
                }

                status.innerHTML = '<span class="text-success"><i class="feather-check me-1"></i> Loaded ' + models.length + ' model(s). Recommended: <code>' + recommended.id + '</code></span>';
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                status.innerHTML = '<span class="text-danger">Network error: ' + err.message + '</span>';
            });
        });
    }

    // Reset Cooldown AJAX Button
    const resetCooldownBtn = document.getElementById('btn-reset-cooldown');
    if (resetCooldownBtn) {
        resetCooldownBtn.addEventListener('click', function () {
            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Resetting...';

            fetch("{{ route('admin.ai-member.reset-cooldown') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (data.success) {
                    showAjaxAlert('success', data.message || '{{ __("ai_member::messages.cooldown_reset_success") }}');
                    const statusText = document.getElementById('cooldownStatusText');
                    if (statusText) {
                        statusText.innerHTML = '<i class="feather-check-circle text-success me-1"></i><span>{{ __("ai_member::messages.bot_ready") }}</span>';
                    }
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showAjaxAlert('error', '{{ __("ai_member::messages.ajax_error") }}');
            });
        });
    }

    // Clear Activity Logs AJAX Button
    const clearLogsBtn = document.getElementById('btn-clear-logs');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you want to clear all activity logs?')) return;
            const btn = this;
            btn.disabled = true;

            fetch("{{ route('admin.ai-member.clear-logs') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    showAjaxAlert('success', data.message || '{{ __("ai_member::messages.logs_cleared") }}');
                    const logsContainer = document.getElementById('activityLogsContainer');
                    if (logsContainer) {
                        logsContainer.innerHTML = '<div class="text-center py-4 text-muted small"><i class="feather-activity fs-3 mb-2 d-block opacity-50"></i>{{ __("ai_member::messages.no_activity_logs") }}</div>';
                    }
                }
            })
            .catch(() => {
                btn.disabled = false;
                showAjaxAlert('error', '{{ __("ai_member::messages.ajax_error") }}');
            });
        });
    }

    // Event Delegation for Task Actions (Run / Cancel)
    const tasksTableContainer = document.getElementById('tasksTableContainer');
    if (tasksTableContainer) {
        tasksTableContainer.addEventListener('click', function (e) {
            const executeBtn = e.target.closest('.js-execute-task-btn');
            const cancelBtn = e.target.closest('.js-cancel-task-btn');

            if (executeBtn) {
                const taskKey = executeBtn.dataset.taskKey;
                const originalHtml = executeBtn.innerHTML;
                executeBtn.disabled = true;
                executeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

                fetch("{{ route('admin.ai-member.execute-task') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ task_key: taskKey })
                })
                .then(res => res.json())
                .then(data => {
                    showAjaxAlert(data.success ? 'success' : 'error', data.message || (data.success ? 'Executed!' : 'Failed.'));
                    if (typeof data.tasks_html === 'string') {
                        tasksTableContainer.innerHTML = data.tasks_html;
                    }
                })
                .catch(() => {
                    executeBtn.disabled = false;
                    executeBtn.innerHTML = originalHtml;
                    showAjaxAlert('error', '{{ __("ai_member::messages.ajax_error") }}');
                });
            }

            if (cancelBtn) {
                if (!confirm('{{ __("ai_member::messages.task_cancel_confirm") }}')) return;

                const taskKey = cancelBtn.dataset.taskKey;
                const originalHtml = cancelBtn.innerHTML;
                cancelBtn.disabled = true;
                cancelBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

                fetch("{{ route('admin.ai-member.cancel-task') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ task_key: taskKey })
                })
                .then(res => res.json())
                .then(data => {
                    showAjaxAlert(data.success ? 'success' : 'error', data.message || '{{ __("ai_member::messages.task_cancelled") }}');
                    if (typeof data.tasks_html === 'string') {
                        tasksTableContainer.innerHTML = data.tasks_html;
                    }
                })
                .catch(() => {
                    cancelBtn.disabled = false;
                    cancelBtn.innerHTML = originalHtml;
                    showAjaxAlert('error', '{{ __("ai_member::messages.ajax_error") }}');
                });
            }
        });
    }
});
</script>

<style>
    .hero-card {
        border-radius: 24px;
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    }
    .glass-stat-card {
        background: rgba(255, 255, 255, 0.12) !important;
        border-radius: 20px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    .section-card {
        border-radius: 20px;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .switch-panel {
        border-radius: 16px;
        background: #f8fafc;
    }
    .section-divider-badge {
        background: #ffffff;
    }
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3) !important;
    }
    .hover-scale-11 {
        transition: transform 0.15s ease;
    }
    .hover-scale-11:hover {
        transform: scale(1.1);
    }
    .activity-icon-badge {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
    }
    .spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
    /* Duralux Dark Mode Support */
    html.app-skin-dark .section-card {
        background: #1e222d !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }
    html.app-skin-dark .switch-panel {
        background: #181b24 !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }
    html.app-skin-dark .section-divider-badge {
        background: #1e222d !important;
    }
    html.app-skin-dark .text-dark {
        color: #f1f5f9 !important;
    }
    html.app-skin-dark .bg-light {
        background: #252b3b !important;
    }
    html.app-skin-dark .border-soft-light {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    html.app-skin-dark .form-control,
    html.app-skin-dark .form-select {
        color: #f1f5f9 !important;
    }
</style>
@endpush
