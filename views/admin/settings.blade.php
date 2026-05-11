@extends('admin::layouts.admin')

@section('title', __('ai_member::messages.settings_title'))
@section('admin_shell_header_mode', 'hidden')

@section('content')
<div class="admin-page ai-member-page">
    <section class="admin-hero">
        <div class="admin-hero__content">
            <ul class="admin-breadcrumb">
                <li><a href="{{ route('admin.index') }}">{{ __('ai_member::messages.breadcrumb_home') }}</a></li>
                <li>{{ __('ai_member::messages.breadcrumb_plugins') }}</li>
                <li>{{ __('ai_member::messages.breadcrumb_current') }}</li>
            </ul>
            <div class="admin-hero__eyebrow">AI Member</div>
            <h1 class="admin-hero__title">{{ __('ai_member::messages.hero_title') }}</h1>
            <p class="admin-hero__copy">
                {{ __('ai_member::messages.hero_copy') }}
            </p>
        </div>
        <div class="admin-hero__actions">
            <div class="admin-summary-grid w-100">
                <div class="admin-summary-card">
                    <span class="admin-summary-label">{{ __('ai_member::messages.status_label') }}</span>
                    <span class="admin-summary-value {{ !empty($config['is_enabled']) ? 'text-success' : 'text-danger' }}">
                        {{ !empty($config['is_enabled']) ? __('ai_member::messages.status_active') : __('ai_member::messages.status_inactive') }}
                    </span>
                    <span class="admin-summary-meta">
                        {{ !empty($config['is_enabled']) ? __('ai_member::messages.status_active_desc') : __('ai_member::messages.status_inactive_desc') }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-4">{{ session('error') }}</div>
    @endif

    <div class="admin-workspace-grid">
        <section class="admin-panel" style="grid-column: span 2;">
            <div class="admin-panel__header">
                <div>
                    <span class="admin-panel__eyebrow">{{ __('ai_member::messages.config_header') }}</span>
                    <h2 class="admin-panel__title">{{ __('ai_member::messages.config_title') }}</h2>
                    <p class="admin-panel__copy mb-0">{{ __('ai_member::messages.config_desc') }}</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <form action="{{ route('admin.ai-member.save') }}" method="POST">
                    @csrf
                    
                    <div class="form-check form-switch mb-4" style="font-size: 1.1rem;">
                        <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" value="1" {{ !empty($config['is_enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold ms-2" for="is_enabled">{{ __('ai_member::messages.is_enabled_label') }}</label>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.api_key_label') }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="feather-key"></i></span>
                                <input type="password" name="api_key" class="form-control" value="{{ $config['api_key'] ?? '' }}" placeholder="{{ __('ai_member::messages.api_key_placeholder') }}">
                            </div>
                            <small class="text-muted">{{ __('ai_member::messages.api_key_help') }}</small>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="feather-user me-2"></i> Bot Identity</h5>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.bot_name_label') }}</label>
                            <input type="text" name="bot_name" class="form-control" value="{{ $config['bot_name'] ?? 'AI Member' }}" placeholder="{{ __('ai_member::messages.bot_name_placeholder') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.bot_username_label') }}</label>
                            <input type="text" name="bot_username" class="form-control" value="{{ $config['bot_username'] ?? 'ai_bot' }}" placeholder="{{ __('ai_member::messages.bot_username_placeholder') }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('ai_member::messages.bot_avatar_label') }}</label>
                        <input type="url" name="bot_avatar" class="form-control" value="{{ $config['bot_avatar'] ?? asset('upload/avatar.png') }}" placeholder="{{ __('ai_member::messages.bot_avatar_placeholder') }}">
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="feather-cpu me-2"></i> AI Behavior</h5>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('ai_member::messages.persona_label') }}</label>
                        <textarea name="persona_prompt" class="form-control" rows="4" placeholder="{{ __('ai_member::messages.persona_placeholder') }}">{{ $config['persona_prompt'] ?? 'You are a helpful and friendly AI assistant member of our social community. You like to share interesting facts about technology and science. Keep your posts and replies short, engaging, and in the language the user speaks to you in (Arabic or English).' }}</textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.post_frequency_label') }}</label>
                            <input type="number" name="post_frequency_hours" class="form-control" value="{{ $config['post_frequency_hours'] ?? '24' }}" min="0">
                            <small class="text-muted">{{ __('ai_member::messages.post_frequency_help') }}</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_messages" id="enable_messages" value="1" {{ !empty($config['enable_messages']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="enable_messages">
                                {{ __('ai_member::messages.enable_messages_label') }}
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_comments" id="enable_comments" value="1" {{ !empty($config['enable_comments']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="enable_comments">
                                {{ __('ai_member::messages.enable_comments_label') }}
                            </label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_reactions" id="enable_reactions" value="1" {{ !empty($config['enable_reactions']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="enable_reactions">
                                {{ __('ai_member::messages.enable_reactions_label') }}
                            </label>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="feather-server me-2"></i> {{ __('ai_member::messages.server_performance_title') }}</h5>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.tick_probability') }}</label>
                            <input type="number" name="tick_probability" class="form-control" value="{{ $config['tick_probability'] ?? '10' }}" min="1" max="100">
                            <small class="text-muted">{{ __('ai_member::messages.tick_probability_help') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.cooldown_minutes') }}</label>
                            <input type="number" name="cooldown_minutes" class="form-control" value="{{ $config['cooldown_minutes'] ?? '5' }}" min="0">
                            <small class="text-muted">{{ __('ai_member::messages.cooldown_minutes_help') }}</small>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="feather-users me-2"></i> Group Management & Moderation</h5>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.group_managed_slug') }}</label>
                            <input type="text" name="managed_group_slug" class="form-control" value="{{ $config['managed_group_slug'] ?? '' }}" placeholder="{{ __('ai_member::messages.group_managed_slug_placeholder') }}">
                            <small class="text-muted">{{ __('ai_member::messages.group_managed_slug_help') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.group_post_frequency') }}</label>
                            <input type="number" name="group_post_frequency_hours" class="form-control" value="{{ $config['group_post_frequency_hours'] ?? '12' }}" min="0">
                            <small class="text-muted">{{ __('ai_member::messages.group_post_frequency_help') }}</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_group_moderation" id="enable_group_moderation" value="1" {{ !empty($config['enable_group_moderation']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="enable_group_moderation">
                                {{ __('ai_member::messages.group_moderation') }}
                            </label>
                            <div class="text-muted small">{{ __('ai_member::messages.group_moderation_help') }}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('ai_member::messages.group_rules') }}</label>
                        <textarea name="group_rules" class="form-control" rows="3" placeholder="{{ __('ai_member::messages.group_rules_placeholder') }}">{{ $config['group_rules'] ?? '' }}</textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5 py-2" style="border-radius: 12px;">
                            <i class="feather-save me-1"></i> {{ __('ai_member::messages.save_settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<style>
    .ai-member-page { gap: 1.5rem; }
    .form-check-input:checked { background-color: #615dfa; border-color: #615dfa; }
</style>
@endsection
