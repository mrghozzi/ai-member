@if(empty($pendingTasks))
    <div class="p-5 text-center">
        <div class="avatar-text avatar-xl bg-soft-success text-success rounded-circle mx-auto mb-3" style="width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center;">
            <i class="feather-check-circle" style="font-size: 2rem;"></i>
        </div>
        <h6 class="fw-bold text-dark mb-1">{{ __('ai_member::messages.no_pending_tasks') }}</h6>
        <p class="text-muted small mb-0">{{ __('ai_member::messages.queue_empty_desc') ?? 'All automated bot operations are completed and up to date.' }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="text-uppercase fs-11 fw-bold text-muted bg-soft-light">
                <tr>
                    <th class="ps-4 py-3" style="width: 60px;">#</th>
                    <th class="py-3" style="width: 25%;">{{ __('ai_member::messages.task_name') }}</th>
                    <th class="py-3">{{ __('ai_member::messages.task_details') }}</th>
                    <th class="py-3" style="width: 15%;">{{ __('ai_member::messages.task_status') }}</th>
                    <th class="text-end pe-4 py-3" style="width: 140px;">{{ __('ai_member::messages.task_actions') }}</th>
                </tr>
            </thead>
            <tbody class="fs-13">
                @foreach($pendingTasks as $task)
                    <tr id="task-row-{{ $task['key'] }}" class="border-bottom border-soft-light transition-all">
                        <td class="ps-4 py-3 text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 rounded fw-bold" style="font-size: 0.8rem;">
                                #{{ $task['queue_index'] ?? $loop->iteration }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-dark fs-14">{{ $task['name'] }}</span>
                                </div>
                                <div>
                                    <span class="badge {{ $task['priority_class'] ?? 'bg-light text-secondary' }} px-2 py-0 rounded" style="font-size: 0.70rem;">
                                        {{ $task['priority_label'] ?? strtoupper($task['type']) }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="text-dark fw-medium">{{ $task['detail'] }}</div>
                            <div class="text-muted small mt-1">
                                <i class="feather-clock me-1"></i>
                                <span>{{ date('Y-m-d H:i:s', $task['scheduled_at'] ?? time()) }}</span>
                            </div>
                        </td>
                        <td class="py-3">
                            @if(($task['status'] ?? '') === 'ready')
                                <span class="badge bg-soft-success text-success rounded-pill px-3 py-1 fw-bold">
                                    <i class="feather-check me-1"></i> {{ __('ai_member::messages.task_status_ready') }}
                                </span>
                            @elseif(($task['status'] ?? '') === 'pending')
                                <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-1 fw-bold">
                                    <i class="feather-loader me-1"></i> {{ __('ai_member::messages.task_status_pending') }}
                                </span>
                            @else
                                <span class="badge bg-soft-warning text-warning rounded-pill px-3 py-1 fw-bold">
                                    <i class="feather-clock me-1"></i> {{ __('ai_member::messages.task_status_scheduled') }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4 py-3">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" 
                                    class="btn btn-sm btn-light-success btn-icon js-execute-task-btn hover-scale-11" 
                                    data-task-key="{{ $task['key'] }}" 
                                    title="{{ __('ai_member::messages.task_execute') }}">
                                    <i class="feather-play"></i>
                                </button>
                                <button type="button" 
                                    class="btn btn-sm btn-light-danger btn-icon js-cancel-task-btn hover-scale-11" 
                                    data-task-key="{{ $task['key'] }}" 
                                    title="{{ __('ai_member::messages.task_cancel') }}">
                                    <i class="feather-x"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
