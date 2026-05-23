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
                    <h5 class="mb-3"><i class="feather-image me-2"></i> {{ __('ai_member::messages.image_generation_title') }}</h5>

                    <div class="form-check form-switch mb-3" style="font-size: 1.05rem;">
                        <input class="form-check-input" type="checkbox" name="enable_image_posts" id="enable_image_posts" value="1" {{ !empty($config['enable_image_posts']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold ms-2" for="enable_image_posts">{{ __('ai_member::messages.enable_image_posts_label') }}</label>
                        <div class="text-muted small">{{ __('ai_member::messages.enable_image_posts_help') }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.image_model_label') }}</label>
                            <input type="text" name="image_model" class="form-control" value="{{ $config['image_model'] ?? 'gemini-2.0-flash-exp' }}" placeholder="gemini-2.0-flash-exp">
                            <small class="text-muted">{{ __('ai_member::messages.image_model_help') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('ai_member::messages.image_post_chance_label') }}</label>
                            <div class="input-group">
                                <input type="number" name="image_post_chance" class="form-control" value="{{ $config['image_post_chance'] ?? '20' }}" min="1" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">{{ __('ai_member::messages.image_post_chance_help') }}</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('ai_member::messages.image_prompt_style_label') }}</label>
                        <textarea name="image_prompt_style" class="form-control" rows="2" placeholder="{{ __('ai_member::messages.image_prompt_style_placeholder') }}">{{ $config['image_prompt_style'] ?? '' }}</textarea>
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

        <!-- Pending Tasks Panel -->
        <section class="admin-panel" style="grid-column: span 2; margin-top: 1.5rem;">
            <div class="admin-panel__header">
                <div>
                    <span class="admin-panel__eyebrow">{{ __('ai_member::messages.pending_tasks_title') }}</span>
                    <h2 class="admin-panel__title">{{ __('ai_member::messages.pending_tasks_title') }}</h2>
                    <p class="admin-panel__copy mb-0">{{ __('ai_member::messages.pending_tasks_desc') }}</p>
                </div>
                <div>
                    @php
                        $cooldownMinutes = (int) ($config['cooldown_minutes'] ?? 5);
                        $lastActionTime = Cache::get('ai_member_global_cooldown', 0);
                        $cooldownEnd = $lastActionTime + ($cooldownMinutes * 60);
                        $isCooldownActive = time() < $cooldownEnd;
                    @endphp
                    @if($isCooldownActive)
                        <span class="badge bg-soft-warning text-warning fs-12 px-3 py-2" style="border-radius: 8px;">
                            <i class="feather-clock me-1"></i> {{ __('ai_member::messages.global_cooldown_active', ['time' => date('H:i:s', $cooldownEnd)]) }}
                        </span>
                    @else
                        <span class="badge bg-soft-success text-success fs-12 px-3 py-2" style="border-radius: 8px;">
                            <i class="feather-check-circle me-1"></i> {{ __('ai_member::messages.global_cooldown_inactive') }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="admin-panel__body">
                <div class="admin-table-wrap">
                    <table class="table table-hover align-middle admin-table admin-table-cardify">
                        <thead>
                            <tr>
                                <th>{{ __('ai_member::messages.task_name') }}</th>
                                <th>{{ __('ai_member::messages.task_type') }}</th>
                                <th>{{ __('ai_member::messages.task_status') }}</th>
                                <th>{{ __('ai_member::messages.task_details') }}</th>
                                <th class="text-end">{{ __('ai_member::messages.task_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingTasks as $task)
                                <tr id="task-row-{{ $task['key'] }}">
                                    <td data-label="{{ __('ai_member::messages.task_name') }}">
                                        <strong>{{ $task['name'] }}</strong>
                                    </td>
                                    <td data-label="{{ __('ai_member::messages.task_type') }}">
                                        <span class="badge bg-soft-secondary text-secondary">{{ strtoupper($task['type']) }}</span>
                                    </td>
                                    <td data-label="{{ __('ai_member::messages.task_status') }}">
                                        @if($task['status'] === 'ready')
                                            <span class="badge bg-soft-success text-success">{{ __('ai_member::messages.task_status_ready') }}</span>
                                        @elseif($task['status'] === 'pending')
                                            <span class="badge bg-soft-primary text-primary">{{ __('ai_member::messages.task_status_pending') }}</span>
                                        @else
                                            <span class="badge bg-soft-warning text-warning">{{ __('ai_member::messages.task_status_scheduled') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('ai_member::messages.task_details') }}">
                                        <span class="text-muted">{{ $task['detail'] }}</span>
                                    </td>
                                    <td data-label="{{ __('ai_member::messages.task_actions') }}" class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 js-execute-task" data-task-key="{{ $task['key'] }}" style="border-radius: 8px;">
                                                <i class="feather-play fs-12"></i> {{ __('ai_member::messages.task_execute') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 js-cancel-task" data-task-key="{{ $task['key'] }}" style="border-radius: 8px;">
                                                <i class="feather-x fs-12"></i> {{ __('ai_member::messages.task_cancel') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="feather-info fs-4 d-block mb-2"></i>
                                        {{ __('ai_member::messages.no_pending_tasks') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
    .ai-member-page { gap: 1.5rem; }
    .form-check-input:checked { background-color: #615dfa; border-color: #615dfa; }
</style>
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
@endpush
