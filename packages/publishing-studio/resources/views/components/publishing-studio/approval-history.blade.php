@php
    use Capell\PublishingStudio\Models\WorkspaceApproval;
    use Illuminate\Support\Collection;

    /** @var Collection<int, WorkspaceApproval> $approvals */
@endphp

<div class="space-y-3">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
        {{ __('capell-admin::workspace.approval_history.title') }}
    </h3>

    @if ($approvals->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('capell-admin::workspace.approval_history.empty') }}
        </p>
    @else
        <ol
            class="relative space-y-3 border-l border-gray-200 pl-4 dark:border-gray-700"
        >
            @foreach ($approvals as $approval)
                @php
                    use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;

                    $color = match ($approval->action) {
                        WorkspaceApprovalActionEnum::Approved => 'bg-green-500',
                        WorkspaceApprovalActionEnum::Rejected => 'bg-red-500',
                        WorkspaceApprovalActionEnum::ChangesRequested => 'bg-amber-500',
                        WorkspaceApprovalActionEnum::Submitted => 'bg-blue-500',
                        default => 'bg-gray-500',
                    };
                    $actorName = $approval->actionable?->name
                        ?? $approval->actionable?->email
                        ?? __('capell-admin::workspace.mail.system_actor');
                @endphp

                <li class="relative">
                    <span
                        class="{{ $color }} absolute -left-[1.375rem] top-1.5 h-2.5 w-2.5 rounded-full"
                    ></span>

                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-white"
                        >
                            {{ $approval->action->getLabel() }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('capell-admin::workspace.approval_history.by') }}
                            {{ $actorName }} ·
                            {{ $approval->created_at?->diffForHumans() }}
                        </span>
                    </div>

                    @if (! empty($approval->notes))
                        <p
                            class="mt-1 whitespace-pre-line rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            {{ $approval->notes }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>
