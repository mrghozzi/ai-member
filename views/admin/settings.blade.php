@extends('admin::layouts.admin')

@section('title', __('ai_member::messages.settings_title'))
@section('admin_shell_header_mode', 'hidden')

@section('content')
<!-- Superdesign Header -->
<div class="row g-0 align-items-center mb-5">
    <div class="col-12 px-4">
        <div class="card border-0 shadow-lg overflow-hidden position-relative" style="border-radius: 24px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
            <!-- Decorative Elements -->
            <div class="position-absolute top-0 end-0 p-5 opacity-10">
                <i class="fa-solid fa-robot" style="font-size: 160px; transform: rotate(-15deg);"></i>
            </div>
            
            <div class="card-body p-5 position-relative z-index-1">
                <div class="row align-items-center">
                    <div class="col-lg-7 text-white">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold animate__animated animate__fadeInDown">AI Member</span>
                        </div>
                        <h1 class="display-5 fw-black mb-2 animate__animated animate__fadeIn">
                            {{ __('ai_member::messages.hero_title') }}
                        </h1>
                        <p class="lead opacity-80 mb-0 animate__animated animate__fadeIn animate__delay-1s">
                            {{ __('ai_member::messages.hero_copy') }}
                        </p>
                    </div>
                    <div class="col-lg-5 text-lg-end mt-4 mt-lg-0 animate__animated animate__fadeInRight">
                        <div class="card border-0 shadow-sm" style="background: rgba(255, 255, 255, 0.1); border-radius: 16px; backdrop-filter: blur(10px);">
                            <div class="card-body p-4 text-start">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-white opacity-80 d-block mb-1 fs-13 text-uppercase fw-bold">{{ __('ai_member::messages.status_label') }}</span>
                                        <h4 class="text-white mb-0 fw-bold">
                                            @if(!empty($config['is_enabled']))
                                                <i class="feather-check-circle text-success me-1"></i> {{ __('ai_member::messages.status_active') }}
                                            @else
                                                <i class="feather-x-circle text-danger me-1"></i> {{ __('ai_member::messages.status_inactive') }}
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="avatar-text avatar-lg text-white rounded-circle" style="background: rgba(255, 255, 255, 0.2);">
                                        <i class="feather-activity"></i>
                                    </div>
                                </div>
                                <div class="text-white opacity-80 fs-12 mt-2">
                                    {{ !empty($config['is_enabled']) ? __('ai_member::messages.status_active_desc') : __('ai_member::messages.status_inactive_desc') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="container-lg px-4 mb-4">
    <div class="alert alert-success shadow-sm border-0" style="border-radius: 16px;">
        <i class="feather-check-circle me-2"></i> {{ session('success') }}
    </div>
</div>
@endif

@if(session('error'))
<div class="container-lg px-4 mb-4">
    <div class="alert alert-danger shadow-sm border-0" style="border-radius: 16px;">
        <i class="feather-alert-triangle me-2"></i> {{ session('error') }}
    </div>
</div>
@endif

<div class="main-content container-lg px-4">
    <div class="row g-4">
        <!-- Config Form -->
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm mb-5" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(var(--nxl-white-rgb), 0.8);">
                <div class="card-header border-0 bg-transparent py-4 ps-4 pe-4">
                    <h5 class="fw-bold mb-1">{{ __('ai_member::messages.config_title') }}</h5>
                    <p class="text-muted mb-0 fs-13">{{ __('ai_member::messages.config_desc') }}</p>
                </div>
                <div class="card-body p-4 p-xl-5 pt-0">
                    <form action="{{ route('admin.ai-member.save') }}" method="POST">
                        @csrf
                        
                        <div class="card border border-soft-light shadow-none mb-4" style="border-radius: 16px; background: #f8fafc;">
                            <div class="card-body p-4">
                                <div class="form-check form-switch form-switch-lg mb-0 d-flex align-items-center">
                                    <input class="form-check-input mt-0" type="checkbox" name="is_enabled" id="is_enabled" value="1" {{ !empty($config['is_enabled']) ? 'checked' : '' }} style="width: 50px; height: 26px;">
                                    <label class="form-check-label fw-bold ms-3 fs-15 text-dark" for="is_enabled">{{ __('ai_member::messages.is_enabled_label') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.api_key_label') }}</label>
                            <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                <span class="input-group-text bg-transparent border-0 text-muted ps-4"><i class="feather-key"></i></span>
                                <input type="password" name="api_key" class="form-control bg-transparent border-0 shadow-none ps-2" value="{{ $config['api_key'] ?? '' }}" placeholder="{{ __('ai_member::messages.api_key_placeholder') }}">
                            </div>
                            <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.api_key_help') }}</small>
                        </div>

                        <div class="position-relative my-5">
                            <hr class="border-soft-light">
                            <div class="position-absolute top-50 start-50 translate-middle px-3" style="background: rgba(var(--nxl-white-rgb), 1);">
                                <h6 class="fw-bold text-muted mb-0"><i class="feather-user me-2 text-primary"></i>Bot Identity</h6>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_name_label') }}</label>
                                <input type="text" name="bot_name" class="form-control form-control-lg border-soft-light bg-light" value="{{ $config['bot_name'] ?? 'AI Member' }}" placeholder="{{ __('ai_member::messages.bot_name_placeholder') }}" style="border-radius: 12px;" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_username_label') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-4">@</span>
                                    <input type="text" name="bot_username" class="form-control bg-transparent border-0 shadow-none ps-2" value="{{ $config['bot_username'] ?? 'ai_bot' }}" placeholder="{{ __('ai_member::messages.bot_username_placeholder') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.bot_avatar_label') }}</label>
                            <input type="url" name="bot_avatar" class="form-control form-control-lg border-soft-light bg-light" value="{{ $config['bot_avatar'] ?? asset('upload/avatar.png') }}" placeholder="{{ __('ai_member::messages.bot_avatar_placeholder') }}" style="border-radius: 12px;">
                        </div>

                        <div class="position-relative my-5">
                            <hr class="border-soft-light">
                            <div class="position-absolute top-50 start-50 translate-middle px-3" style="background: rgba(var(--nxl-white-rgb), 1);">
                                <h6 class="fw-bold text-muted mb-0"><i class="feather-cpu me-2 text-primary"></i>AI Behavior</h6>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.persona_label') }}</label>
                            <textarea name="persona_prompt" class="form-control border-soft-light bg-light p-3" rows="4" placeholder="{{ __('ai_member::messages.persona_placeholder') }}" style="border-radius: 12px;">{{ $config['persona_prompt'] ?? 'You are a helpful and friendly AI assistant member of our social community. You like to share interesting facts about technology and science. Keep your posts and replies short, engaging, and in the language the user speaks to you in (Arabic or English).' }}</textarea>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.post_frequency_label') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <input type="number" name="post_frequency_hours" class="form-control bg-transparent border-0 shadow-none ps-4" value="{{ $config['post_frequency_hours'] ?? '24' }}" min="0">
                                    <span class="input-group-text bg-transparent border-0 text-muted pe-4">Hours</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.post_frequency_help') }}</small>
                            </div>
                        </div>

                        <div class="card border border-soft-light shadow-none mb-4" style="border-radius: 16px; background: #f8fafc;">
                            <div class="card-body p-4">
                                <div class="row g-4">
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

                        <div class="card border border-soft-light shadow-none mb-4" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <div class="form-check form-switch d-flex align-items-center mb-0">
                                    <input class="form-check-input mt-0" type="checkbox" name="enable_auto_block" id="enable_auto_block" value="1" {{ !empty($config['enable_auto_block']) ? 'checked' : '' }} style="width: 40px; height: 20px;">
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold text-dark mb-1" for="enable_auto_block">{{ __('ai_member::messages.enable_auto_block_label') }}</label>
                                        <div class="text-muted small">{{ __('ai_member::messages.enable_auto_block_help') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="position-relative my-5">
                            <hr class="border-soft-light">
                            <div class="position-absolute top-50 start-50 translate-middle px-3" style="background: rgba(var(--nxl-white-rgb), 1);">
                                <h6 class="fw-bold text-muted mb-0"><i class="feather-image me-2 text-primary"></i>{{ __('ai_member::messages.image_generation_title') }}</h6>
                            </div>
                        </div>

                        <div class="card border border-soft-light shadow-none mb-4" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <div class="form-check form-switch d-flex align-items-center mb-0">
                                    <input class="form-check-input mt-0" type="checkbox" name="enable_image_posts" id="enable_image_posts" value="1" {{ !empty($config['enable_image_posts']) ? 'checked' : '' }} style="width: 40px; height: 20px;">
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
                                <input type="text" name="image_model" class="form-control form-control-lg border-soft-light bg-light" value="{{ $config['image_model'] ?? 'gemini-2.0-flash-exp' }}" placeholder="gemini-2.0-flash-exp" style="border-radius: 12px;">
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.image_model_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.image_post_chance_label') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <input type="number" name="image_post_chance" class="form-control bg-transparent border-0 shadow-none ps-4" value="{{ $config['image_post_chance'] ?? '20' }}" min="1" max="100">
                                    <span class="input-group-text bg-transparent border-0 text-muted pe-4">%</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.image_post_chance_help') }}</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.image_prompt_style_label') }}</label>
                            <textarea name="image_prompt_style" class="form-control border-soft-light bg-light p-3" rows="2" placeholder="{{ __('ai_member::messages.image_prompt_style_placeholder') }}" style="border-radius: 12px;">{{ $config['image_prompt_style'] ?? '' }}</textarea>
                        </div>

                        <div class="position-relative my-5">
                            <hr class="border-soft-light">
                            <div class="position-absolute top-50 start-50 translate-middle px-3" style="background: rgba(var(--nxl-white-rgb), 1);">
                                <h6 class="fw-bold text-muted mb-0"><i class="feather-server me-2 text-primary"></i>{{ __('ai_member::messages.server_performance_title') }}</h6>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.tick_probability') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <input type="number" name="tick_probability" class="form-control bg-transparent border-0 shadow-none ps-4" value="{{ $config['tick_probability'] ?? '10' }}" min="1" max="100">
                                    <span class="input-group-text bg-transparent border-0 text-muted pe-4">%</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.tick_probability_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.cooldown_minutes') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <input type="number" name="cooldown_minutes" class="form-control bg-transparent border-0 shadow-none ps-4" value="{{ $config['cooldown_minutes'] ?? '5' }}" min="0">
                                    <span class="input-group-text bg-transparent border-0 text-muted pe-4">Min</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.cooldown_minutes_help') }}</small>
                            </div>
                        </div>

                        <div class="position-relative my-5">
                            <hr class="border-soft-light">
                            <div class="position-absolute top-50 start-50 translate-middle px-3" style="background: rgba(var(--nxl-white-rgb), 1);">
                                <h6 class="fw-bold text-muted mb-0"><i class="feather-users me-2 text-primary"></i>Group Management</h6>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_managed_slug') }}</label>
                                <input type="text" name="managed_group_slug" class="form-control form-control-lg border-soft-light bg-light" value="{{ $config['managed_group_slug'] ?? '' }}" placeholder="{{ __('ai_member::messages.group_managed_slug_placeholder') }}" style="border-radius: 12px;">
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.group_managed_slug_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_post_frequency') }}</label>
                                <div class="input-group input-group-lg border-soft-light bg-light" style="border-radius: 12px; overflow: hidden;">
                                    <input type="number" name="group_post_frequency_hours" class="form-control bg-transparent border-0 shadow-none ps-4" value="{{ $config['group_post_frequency_hours'] ?? '12' }}" min="0">
                                    <span class="input-group-text bg-transparent border-0 text-muted pe-4">Hours</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="feather-info me-1"></i>{{ __('ai_member::messages.group_post_frequency_help') }}</small>
                            </div>
                        </div>

                        <div class="card border border-soft-light shadow-none mb-4" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <div class="form-check form-switch d-flex align-items-center mb-0">
                                    <input class="form-check-input mt-0" type="checkbox" name="enable_group_moderation" id="enable_group_moderation" value="1" {{ !empty($config['enable_group_moderation']) ? 'checked' : '' }} style="width: 40px; height: 20px;">
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold text-dark mb-1" for="enable_group_moderation">{{ __('ai_member::messages.group_moderation') }}</label>
                                        <div class="text-muted small">{{ __('ai_member::messages.group_moderation_help') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-2">{{ __('ai_member::messages.group_rules') }}</label>
                            <textarea name="group_rules" class="form-control border-soft-light bg-light p-3" rows="3" placeholder="{{ __('ai_member::messages.group_rules_placeholder') }}" style="border-radius: 12px;">{{ $config['group_rules'] ?? '' }}</textarea>
                        </div>

                        <div class="text-end border-top border-soft-light pt-4 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm hover-scale" style="border-radius: 14px;">
                                <i class="feather-save me-2"></i> {{ __('ai_member::messages.save_settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Pending Tasks Panel -->
            <div class="card border-0 shadow-sm mb-5" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(var(--nxl-white-rgb), 0.8);">
                <div class="card-header border-0 bg-transparent py-4 ps-4 pe-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1">{{ __('ai_member::messages.pending_tasks_title') }}</h5>
                        <p class="text-muted mb-0 fs-13">{{ __('ai_member::messages.pending_tasks_desc') }}</p>
                    </div>
                    <div>
                        @php
                            $cooldownMinutes = (int) ($config['cooldown_minutes'] ?? 5);
                            $lastActionTime = Cache::get('ai_member_global_cooldown', 0);
                            $cooldownEnd = $lastActionTime + ($cooldownMinutes * 60);
                            $isCooldownActive = time() < $cooldownEnd;
                        @endphp
                        @if($isCooldownActive)
                            <span class="badge bg-soft-warning text-warning fs-12 px-3 py-2 fw-bold" style="border-radius: 8px;">
                                <i class="feather-clock me-1"></i> {{ __('ai_member::messages.global_cooldown_active', ['time' => date('H:i:s', $cooldownEnd)]) }}
                            </span>
                        @else
                            <span class="badge bg-soft-success text-success fs-12 px-3 py-2 fw-bold" style="border-radius: 8px;">
                                <i class="feather-check-circle me-1"></i> {{ __('ai_member::messages.global_cooldown_inactive') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body px-0 pt-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-uppercase fs-11 fw-bold text-muted bg-soft-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('ai_member::messages.task_name') }}</th>
                                    <th class="py-3">{{ __('ai_member::messages.task_type') }}</th>
                                    <th class="py-3">{{ __('ai_member::messages.task_status') }}</th>
                                    <th class="py-3">{{ __('ai_member::messages.task_details') }}</th>
                                    <th class="text-end pe-4 py-3">{{ __('ai_member::messages.task_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="fs-13">
                                @forelse($pendingTasks as $task)
                                    <tr id="task-row-{{ $task['key'] }}" class="hover-bg-light transition-all border-bottom border-soft-light">
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-text avatar-sm bg-soft-primary text-primary rounded-circle me-3">
                                                    <i class="feather-cpu"></i>
                                                </div>
                                                <span class="fw-bold text-dark fs-14">{{ $task['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge bg-light text-secondary border border-soft-light rounded-pill px-3 py-1 fw-bold">{{ strtoupper($task['type']) }}</span>
                                        </td>
                                        <td class="py-3">
                                            @if($task['status'] === 'ready')
                                                <span class="badge bg-soft-success text-success rounded-pill px-3 py-1 fw-bold"><i class="feather-check me-1"></i> {{ __('ai_member::messages.task_status_ready') }}</span>
                                            @elseif($task['status'] === 'pending')
                                                <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-1 fw-bold"><i class="feather-loader me-1"></i> {{ __('ai_member::messages.task_status_pending') }}</span>
                                            @else
                                                <span class="badge bg-soft-warning text-warning rounded-pill px-3 py-1 fw-bold"><i class="feather-clock me-1"></i> {{ __('ai_member::messages.task_status_scheduled') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            <span class="text-muted">{{ $task['detail'] }}</span>
                                        </td>
                                        <td class="text-end pe-4 py-3">
                                            <button type="button" class="btn btn-sm btn-icon btn-glass btn-light-success hover-scale-11 js-execute-task me-1" data-task-key="{{ $task['key'] }}" title="{{ __('ai_member::messages.task_execute') }}">
                                                <i class="feather-play"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-icon btn-glass btn-light-danger hover-scale-11 js-cancel-task" data-task-key="{{ $task['key'] }}" title="{{ __('ai_member::messages.task_cancel') }}">
                                                <i class="feather-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="avatar-text avatar-xl bg-soft-light text-muted rounded-circle mx-auto mb-3" style="width: 64px; height: 64px;">
                                                <i class="feather-inbox fs-24"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">{{ __('ai_member::messages.no_pending_tasks') }}</h6>
                                            <p class="text-muted small mb-0">All automated operations are up to date.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Handle Execute Now
    document.querySelectorAll('.js-execute-task').forEach(function (button) {
        button.addEventListener('click', function () {
            const taskKey = this.dataset.taskKey;
            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch('{{ route("admin.ai-member.execute-task") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ task_key: taskKey })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ __("ai_member::messages.task_execute_failed") }}');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                console.error('Error executing task:', error);
                alert('An error occurred while executing the task.');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });

    // Handle Cancel
    document.querySelectorAll('.js-cancel-task').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!confirm('{{ __("ai_member::messages.task_cancel_confirm") ?? "Are you sure you want to cancel/ignore this task?" }}')) {
                return;
            }

            const taskKey = this.dataset.taskKey;
            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch('{{ route("admin.ai-member.cancel-task") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ task_key: taskKey })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('task-row-' + taskKey);
                    if (row) {
                        row.remove();
                        const remainingRows = document.querySelectorAll('tbody tr[id^="task-row-"]');
                        if (remainingRows.length === 0) {
                            location.reload();
                        }
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Failed to cancel task.');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                console.error('Error canceling task:', error);
                alert('An error occurred while canceling the task.');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });
});
</script>
<style>
    .fw-black { font-weight: 900; }
    .opacity-10 { opacity: 0.1; }
    .opacity-80 { opacity: 0.8; }
    .z-index-1 { z-index: 1; }
    .hover-scale { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-scale:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; }
    .hover-scale-11 { transition: transform 0.2s ease; }
    .hover-scale-11:hover { transform: scale(1.1); }
    .transition-all { transition: all 0.3s ease; }
    .btn-glass {
        background: rgba(var(--nxl-white-rgb), 0.5);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(var(--nxl-dark-rgb), 0.1);
    }
    .custom-checkbox .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.15rem;
    }
</style>
@endpush
