@php
    use Capell\PublishingStudio\Services\MediaDiffService;
    use Illuminate\Support\Collection;

    /** @var Collection<int, array<string, mixed>> $diffs */
    $mediaDiffService = new MediaDiffService;
@endphp

<div>
    {{-- Toolbar --}}
    <div class="mb-4 flex items-center gap-3">
        <x-filament::button wire:click="toggleMode" color="gray" size="sm">
            @if ($mode === 'side-by-side')
                {{ __('capell-admin::workspace.compare.switch_inline') }}
            @else
                {{ __('capell-admin::workspace.compare.switch_side_by_side') }}
            @endif
        </x-filament::button>

        <x-filament::button wire:click="toggleUnchanged" color="gray" size="sm">
            @if ($showUnchanged)
                {{ __('capell-admin::workspace.compare.hide_unchanged') }}
            @else
                {{ __('capell-admin::workspace.compare.show_unchanged') }}
            @endif
        </x-filament::button>
    </div>

    @if ($diffs->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('capell-admin::workspace.compare.empty') }}
            </p>
        </x-filament::section>
    @else
        <div class="space-y-6">
            @foreach ($diffs as $diff)
                @if ($diff['kind'] === 'unchanged' && ! $showUnchanged)
                    @continue
                @endif

                @php
                    $kindLabel = match ($diff['kind']) {
                        'added' => __('capell-admin::workspace.compare.kind_added'),
                        'deleted' => __('capell-admin::workspace.compare.kind_deleted'),
                        'unchanged' => __('capell-admin::workspace.compare.kind_unchanged'),
                        default => __('capell-admin::workspace.compare.kind_modified'),
                    };
                    $heading = class_basename($diff['model']) . ' — ' . ($diff['uuid'] ?? '#' . $diff['workspace_id']);
                @endphp

                <x-filament::section
                    :heading="$heading"
                    :description="$kindLabel"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-gray-200 dark:border-gray-700"
                            >
                                <th
                                    class="py-2 pr-4 text-left font-medium text-gray-500"
                                >
                                    {{ __('capell-admin::workspace.compare.field') }}
                                </th>
                                @if ($mode === 'side-by-side')
                                    <th
                                        class="py-2 pr-4 text-left font-medium text-gray-500"
                                    >
                                        {{ __('capell-admin::workspace.compare.before') }}
                                    </th>
                                    <th
                                        class="py-2 text-left font-medium text-gray-500"
                                    >
                                        {{ __('capell-admin::workspace.compare.after') }}
                                    </th>
                                @else
                                    <th
                                        class="py-2 text-left font-medium text-gray-500"
                                    >
                                        {{ __('capell-admin::workspace.compare.value') }}
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($diff['attributes'] as $field => $attr)
                                @if ($attr['status'] === 'unchanged' && ! $showUnchanged)
                                    @continue
                                @endif

                                <tr
                                    class="border-b border-gray-100 align-top dark:border-gray-800"
                                >
                                    <td
                                        class="py-2 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300"
                                    >
                                        {{ $field }}
                                        @if ($attr['status'] !== 'unchanged')
                                            <span
                                                class="@if ($attr['status'] === 'added')
                                                    bg-green-100
                                                    text-green-700
                                                    dark:bg-green-900
                                                    dark:text-green-300
                                                @elseif ($attr['status'] === 'removed')
                                                    bg-red-100
                                                    text-red-700
                                                    dark:bg-red-900
                                                    dark:text-red-300
                                                @else
                                                    bg-amber-100
                                                    text-amber-700
                                                    dark:bg-amber-900
                                                    dark:text-amber-300
                                                @endif ml-1 inline-block rounded px-1 py-0.5 text-[10px] font-semibold"
                                            >
                                                {{ $attr['status'] }}
                                            </span>
                                        @endif
                                    </td>

                                    @if ($mode === 'side-by-side')
                                        @if ($attr['status'] === 'unchanged')
                                            <td
                                                class="py-2 pr-4 text-gray-400 dark:text-gray-500"
                                                colspan="2"
                                            >
                                                {{ $attr['value'] === null ? '—' : (string) $attr['value'] }}
                                            </td>
                                        @elseif ($attr['status'] === 'added')
                                            <td
                                                class="py-2 pr-4 text-gray-400 dark:text-gray-500"
                                            >
                                                —
                                            </td>
                                            <td
                                                class="py-2 text-green-700 dark:text-green-300"
                                            >
                                                {{ $attr['value'] === null ? '—' : (string) $attr['value'] }}
                                            </td>
                                        @elseif ($attr['status'] === 'removed')
                                            <td
                                                class="py-2 pr-4 text-red-700 line-through dark:text-red-300"
                                            >
                                                {{ $attr['value'] === null ? '—' : (string) $attr['value'] }}
                                            </td>
                                            <td
                                                class="py-2 text-gray-400 dark:text-gray-500"
                                            >
                                                —
                                            </td>
                                        @else
                                            @php
                                                $isMedia = $mediaDiffService->looksLikeMedia($attr['before'] ?? '') || $mediaDiffService->looksLikeMedia($attr['after'] ?? '');
                                            @endphp

                                            @if ($isMedia)
                                                <td class="py-2 pr-4">
                                                    @if ($attr['before'] ?? null)
                                                        <img
                                                            src="{{ $attr['before'] }}"
                                                            class="max-h-32 rounded border border-gray-200 dark:border-gray-700"
                                                            alt="before"
                                                        />
                                                    @else
                                                        <span
                                                            class="text-gray-400"
                                                        >
                                                            —
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-2">
                                                    @if ($attr['after'] ?? null)
                                                        <img
                                                            src="{{ $attr['after'] }}"
                                                            class="max-h-32 rounded border border-gray-200 dark:border-gray-700"
                                                            alt="after"
                                                        />
                                                    @else
                                                        <span
                                                            class="text-gray-400"
                                                        >
                                                            —
                                                        </span>
                                                    @endif
                                                </td>
                                            @else
                                                <td
                                                    class="py-2 pr-4 text-gray-500 line-through"
                                                >
                                                    {{ $attr['before'] === null ? '—' : (string) $attr['before'] }}
                                                </td>
                                                <td
                                                    class="py-2 text-gray-900 dark:text-gray-100"
                                                >
                                                    {{ $attr['after'] === null ? '—' : (string) $attr['after'] }}
                                                </td>
                                            @endif
                                        @endif
                                    @else
                                        {{-- Inline mode --}}
                                        @if ($attr['status'] === 'unchanged')
                                            <td
                                                class="py-2 text-gray-400 dark:text-gray-500"
                                            >
                                                {{ $attr['value'] === null ? '—' : (string) $attr['value'] }}
                                            </td>
                                        @elseif (in_array($attr['status'], ['added', 'removed'], true))
                                            <td
                                                class="py-2 text-gray-900 dark:text-gray-100"
                                            >
                                                {{ $attr['value'] === null ? '—' : (string) $attr['value'] }}
                                            </td>
                                        @else
                                            @php
                                                $isMedia = $mediaDiffService->looksLikeMedia($attr['before'] ?? '') || $mediaDiffService->looksLikeMedia($attr['after'] ?? '');
                                            @endphp

                                            @if ($isMedia)
                                                <td class="py-2">
                                                    <div class="flex gap-4">
                                                        @if ($attr['before'] ?? null)
                                                            <img
                                                                src="{{ $attr['before'] }}"
                                                                class="max-h-32 rounded border border-gray-200 dark:border-gray-700"
                                                                alt="before"
                                                            />
                                                        @endif

                                                        @if ($attr['after'] ?? null)
                                                            <img
                                                                src="{{ $attr['after'] }}"
                                                                class="max-h-32 rounded border border-gray-200 dark:border-gray-700"
                                                                alt="after"
                                                            />
                                                        @endif
                                                    </div>
                                                </td>
                                            @else
                                                <td class="py-2">
                                                    <span
                                                        class="text-gray-500 line-through"
                                                    >
                                                        {{ $attr['before'] === null ? '—' : (string) $attr['before'] }}
                                                    </span>
                                                    <span
                                                        class="mx-1 text-gray-400"
                                                    >
                                                        →
                                                    </span>
                                                    <span
                                                        class="text-gray-900 dark:text-gray-100"
                                                    >
                                                        {{ $attr['after'] === null ? '—' : (string) $attr['after'] }}
                                                    </span>
                                                </td>
                                            @endif
                                        @endif
                                    @endif
                                </tr>

                                {{-- Field comment thread --}}
                                <tr>
                                    <td
                                        colspan="{{ $mode === 'side-by-side' ? 3 : 2 }}"
                                        class="pb-2 pt-0"
                                    >
                                        @livewire('capell-publishing-studio::field-comment-thread',
                                            [
                                                'workspaceId' => $workspaceId,
                                                'entityType' => $diff['model'],
                                                'entityUuid' => $diff['uuid'] ?? '',
                                                'fieldPath' => $field,
                                            ],
                                            key('comment-' . ($diff['uuid'] ?? $diff['workspace_id']) . '-' . $field))
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</div>
