@php
    use Capell\PublishingStudio\Models\WorkspaceFieldComment;
    use Illuminate\Support\Collection;

    /** @var Collection<int, WorkspaceFieldComment> $comments */
@endphp

<div class="space-y-4">
    @if ($comments->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('capell-admin::workspace.field_comments.empty') }}
        </p>
    @else
        <ol class="space-y-3">
            @foreach ($comments as $comment)
                @php
                    $authorName = $comment->author?->name
                        ?? $comment->author?->email
                        ?? __('capell-admin::workspace.mail.system_actor');
                @endphp

                <li
                    class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p
                            class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200"
                        >
                            {{ $comment->body }}
                        </p>

                        @if ($comment->isResolved())
                            <button
                                type="button"
                                wire:click="reopenComment({{ $comment->id }})"
                                class="shrink-0 rounded text-xs text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                            >
                                {{ __('capell-admin::workspace.field_comments.reopen') }}
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="resolveComment({{ $comment->id }})"
                                class="shrink-0 rounded text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                            >
                                {{ __('capell-admin::workspace.field_comments.resolve') }}
                            </button>
                        @endif
                    </div>

                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $authorName }} ·
                            {{ $comment->created_at?->diffForHumans() }}
                        </span>

                        @if ($comment->isResolved())
                            <span
                                class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300"
                            >
                                {{ __('capell-admin::workspace.field_comments.resolved') }}
                            </span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <form wire:submit.prevent="postComment" class="space-y-2">
        <textarea
            wire:model="newComment"
            rows="2"
            placeholder="{{ __('capell-admin::workspace.field_comments.placeholder') }}"
            class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
        ></textarea>

        <button
            type="submit"
            class="bg-primary-600 hover:bg-primary-700 focus:ring-primary-500 rounded-md px-3 py-1.5 text-xs font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-1"
        >
            {{ __('capell-admin::workspace.field_comments.post') }}
        </button>
    </form>
</div>
