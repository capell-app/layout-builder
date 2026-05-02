@php
    use Capell\Admin\Filament\Resources\Pages\Pages\PageVersionHistoryPage;
    use Capell\Core\Models\Page;
    use Capell\Workspaces\Models\Workspace;
    use Illuminate\Support\Collection;

    /** @var Collection<int, Page> $copies */
    /** @var Collection<int, array<string, mixed>> $diffs */
    /** @var Workspace|null $selectedWorkspace */
    /** @var PageVersionHistoryPage $this */
@endphp

<x-filament-panels::page>
    <style>
        .diff-wrapper.diff {
            --tab-size: 4;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #e5e7eb;
            color: #111827;
            empty-cells: show;
            font-family:
                ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            width: 100%;
            word-break: break-all;
        }
        .dark .diff-wrapper.diff {
            border-color: #374151;
            color: #f3f4f6;
        }
        .diff-wrapper.diff td,
        .diff-wrapper.diff th {
            border: none;
            padding: 2px 6px;
            background: #fff;
        }
        .dark .diff-wrapper.diff td,
        .dark .diff-wrapper.diff th {
            background: #1f2937;
        }
        .diff-wrapper.diff td:empty:after,
        .diff-wrapper.diff th:empty:after {
            content: ' ';
            visibility: hidden;
        }
        .diff-wrapper.diff tbody th {
            background: #f3f4f6;
            border-right: 1px solid #e5e7eb;
            text-align: right;
            vertical-align: top;
            width: 3em;
            font-weight: normal;
            color: #9ca3af;
        }
        .dark .diff-wrapper.diff tbody th {
            background: #374151;
            border-right-color: #4b5563;
            color: #6b7280;
        }
        .diff-wrapper.diff tbody th.sign {
            background: #fff;
            border-right: none;
            padding: 2px 0;
            text-align: center;
            width: 1em;
        }
        .dark .diff-wrapper.diff tbody th.sign {
            background: #1f2937;
        }
        .diff-wrapper.diff tbody th.sign.del {
            background: #fef2f2;
        }
        .diff-wrapper.diff tbody th.sign.ins {
            background: #f0fdf4;
        }
        .dark .diff-wrapper.diff tbody th.sign.del {
            background: #450a0a;
        }
        .dark .diff-wrapper.diff tbody th.sign.ins {
            background: #052e16;
        }
        .diff-wrapper.diff.diff-html {
            white-space: pre-wrap;
            tab-size: var(--tab-size);
        }
        .diff-wrapper.diff.diff-html .change.change-eq .old,
        .diff-wrapper.diff.diff-html .change.change-eq .new {
            background: #fff;
        }
        .dark .diff-wrapper.diff.diff-html .change.change-eq .old,
        .dark .diff-wrapper.diff.diff-html .change.change-eq .new {
            background: #1f2937;
        }
        .diff-wrapper.diff.diff-html .change .old {
            background: #fef2f2;
        }
        .diff-wrapper.diff.diff-html .change .new {
            background: #f0fdf4;
        }
        .dark .diff-wrapper.diff.diff-html .change .old {
            background: #450a0a;
        }
        .dark .diff-wrapper.diff.diff-html .change .new {
            background: #052e16;
        }
        .diff-wrapper.diff.diff-html .change .rep {
            background: #fefce8;
        }
        .diff-wrapper.diff.diff-html .change .old.none,
        .diff-wrapper.diff.diff-html .change .new.none,
        .diff-wrapper.diff.diff-html .change .rep.none {
            background: transparent;
            cursor: not-allowed;
        }
        .diff-wrapper.diff.diff-html .change ins,
        .diff-wrapper.diff.diff-html .change del {
            font-weight: bold;
            text-decoration: none;
        }
        .diff-wrapper.diff.diff-html .change ins {
            background: #86efac;
        }
        .diff-wrapper.diff.diff-html .change del {
            background: #fca5a5;
        }
        .dark .diff-wrapper.diff.diff-html .change ins {
            background: #166534;
            color: #bbf7d0;
        }
        .dark .diff-wrapper.diff.diff-html .change del {
            background: #7f1d1d;
            color: #fecaca;
        }
    </style>

    <div class="flex items-start gap-6">
        {{-- Main diff area --}}
        <div class="min-w-0 flex-1 space-y-4">
            @if ($selectedWorkspace === null)
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('capell-admin::message.version_history_select_prompt') }}
                    </p>
                </x-filament::section>
            @elseif ($diffs->isEmpty())
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('capell-admin::workspace.compare.empty') }}
                    </p>
                </x-filament::section>
            @else
                <div class="space-y-4">
                    @foreach ($diffs as $diff)
                        <x-filament::section
                            :heading="class_basename($diff['model']) . ' — ' . ($diff['uuid'] ?? '#' . $diff['workspace_id'])"
                            :description="
                                $diff['kind'] === 'added'
                                ? __('capell-admin::workspace.compare.kind_added')
                                : ($diff['kind'] === 'deleted'
                                    ? __('capell-admin::workspace.compare.kind_deleted')
                                    : __('capell-admin::workspace.compare.kind_modified'))
                            "
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
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($diff['changes'] as $field => $change)
                                        <tr
                                            class="border-b border-gray-100 align-top dark:border-gray-800"
                                        >
                                            <td
                                                class="py-2 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300"
                                            >
                                                {{ $field }}
                                            </td>
                                            @if ($this->isLongText($change['before']) || $this->isLongText($change['after']))
                                                <td colspan="2" class="py-2">
                                                    {!! $this->renderHtmlDiff($change['before'], $change['after']) !!}
                                                </td>
                                            @else
                                                <td
                                                    class="py-2 pr-4 text-red-600 dark:text-red-400"
                                                >
                                                    {{ $change['before'] === null ? '—' : (string) $change['before'] }}
                                                </td>
                                                <td
                                                    class="py-2 text-green-700 dark:text-green-400"
                                                >
                                                    {{ $change['after'] === null ? '—' : (string) $change['after'] }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-filament::section>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Version timeline sidebar --}}
        <div class="w-72 shrink-0">
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="border-b border-gray-200 px-4 py-3 dark:border-gray-700"
                >
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        {{ __('capell-admin::button.version_history') }}
                    </h3>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    {{-- Current live version --}}
                    <div class="flex items-start gap-3 px-4 py-3">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center"
                        >
                            <div
                                class="h-2.5 w-2.5 rounded-full bg-green-500"
                            ></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {{ __('capell-admin::message.version_history_current') }}
                            </p>
                            @if ($this->record->updated_at)
                                <p
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $this->record->updated_at->format('M j, Y \a\t g:ia') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Draft workspace versions --}}
                    @forelse ($copies as $copy)
                        @if ($copy->isLive())
                            @continue
                        @endif

                        @php
                            $isSelected = $this->selectedWorkspaceId === $copy->workspace_id;
                            $creatorName = $copy->workspace?->creator?->getAttribute('name');
                            $updatedAt = $copy->updated_at;
                        @endphp

                        <button
                            type="button"
                            wire:click="selectVersion({{ $copy->workspace_id }})"
                            class="{{ $isSelected ? 'bg-primary-50 dark:bg-primary-900/20' : '' }} flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center"
                            >
                                <div
                                    class="{{ $isSelected ? 'bg-primary-600' : 'border-2 border-gray-300 dark:border-gray-600' }} h-2.5 w-2.5 rounded-full"
                                ></div>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1">
                                <p
                                    class="{{ $isSelected ? 'text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white' }} truncate text-sm font-medium"
                                >
                                    {{ $copy->workspace?->name ?? '—' }}
                                </p>
                                @if ($updatedAt)
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $updatedAt->format('M j, Y \a\t g:ia') }}
                                    </p>
                                @endif

                                @if ($creatorName)
                                    <p
                                        class="text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        {{ $creatorName }}
                                    </p>
                                @endif

                                @if ($copy->workspace?->status)
                                    <span
                                        class="{{
                                            match ($copy->workspace->status->value ?? '') {
                                                'open' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                                'in_review' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                'approved' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                                'published' => 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                                default => 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                            }
                                        }} inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ $copy->workspace->status->getLabel() }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center">
                            <p class="text-sm text-gray-400 dark:text-gray-500">
                                {{ __('capell-admin::message.version_history_no_drafts') }}
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Selected version actions --}}
                @if ($selectedWorkspace !== null)
                    @php
                        $selectedCopy = $copies->first(fn (Page $copy): bool => $copy->workspace_id === $this->selectedWorkspaceId);
                        $previewUrl = rescue(fn (): ?string => $selectedCopy?->pageUrl?->full_url, null, false);
                    @endphp

                    <div
                        class="space-y-2 border-t border-gray-200 p-3 dark:border-gray-700"
                    >
                        <a
                            href="{{ $this->getWorkspaceUrl($selectedWorkspace) }}"
                            class="border-primary-600 text-primary-700 hover:bg-primary-50 dark:border-primary-400 dark:text-primary-300 dark:hover:bg-primary-900/20 flex w-full items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-colors"
                        >
                            <x-filament::icon
                                icon="heroicon-o-arrow-top-right-on-square"
                                class="h-4 w-4"
                            />
                            {{ __('capell-admin::message.version_history_open_workspace') }}
                        </a>
                        @if ($previewUrl)
                            <a
                                href="{{ $previewUrl }}"
                                target="_blank"
                                class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-eye"
                                    class="h-4 w-4"
                                />
                                {{ __('capell-admin::message.version_history_preview') }}
                            </a>
                        @endif

                        @if ($selectedCopy !== null)
                            <button
                                type="button"
                                wire:click="deleteVersion({{ $selectedCopy->id }})"
                                wire:confirm="{{ __('capell-admin::message.version_history_delete_confirm') }}"
                                class="flex w-full items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-trash"
                                    class="h-4 w-4"
                                />
                                {{ __('capell-admin::message.version_history_delete') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
