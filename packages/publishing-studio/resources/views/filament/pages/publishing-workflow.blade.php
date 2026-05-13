<x-filament-panels::page>
    @php
        $panels = $this->panels();
    @endphp

    <div class="space-y-6" data-publishing-workflow-command-center>
        @if ($panels === [])
            <section
                class="rounded-lg border border-gray-200 bg-white p-6 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900"
                aria-labelledby="publishing-workflow-empty-heading"
            >
                <h2
                    id="publishing-workflow-empty-heading"
                    class="text-sm font-semibold text-gray-950 dark:text-white"
                >
                    {{ __('capell-publishing-studio::workflow.empty.title') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('capell-publishing-studio::workflow.empty.description') }}
                </p>
            </section>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($panels as $panel)
                    <section
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900"
                        data-workflow-panel="{{ $panel->key }}"
                        aria-labelledby="workflow-panel-{{ $panel->key }}"
                    >
                        <div
                            class="mb-4 flex items-start justify-between gap-3"
                        >
                            <div>
                                <h2
                                    id="workflow-panel-{{ $panel->key }}"
                                    class="text-sm font-semibold text-gray-950 dark:text-white"
                                >
                                    {{ $panel->label }}
                                </h2>
                                <p
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ $panel->description }}
                                </p>
                            </div>

                            <span
                                class="rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-600/10 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"
                            >
                                {{ $panel->totalCount() }}
                            </span>
                        </div>

                        @if ($panel->actions === [])
                            <div
                                class="rounded-md border border-dashed border-gray-200 p-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400"
                            >
                                {{ __('capell-publishing-studio::workflow.empty_panel') }}
                            </div>
                        @else
                            <div
                                class="divide-y divide-gray-100 dark:divide-white/10"
                            >
                                @foreach ($panel->actions as $action)
                                    @php
                                        $severityClass = match ($action->severity) {
                                            'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-300 dark:ring-danger-400/30',
                                            'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/30',
                                            'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-300 dark:ring-success-400/30',
                                            default => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-300 dark:ring-info-400/30',
                                        };
                                        $severityLabel = __('capell-publishing-studio::workflow.severity.' . $action->severity);
                                    @endphp

                                    <div
                                        class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-sm font-medium text-gray-950 dark:text-white"
                                                >
                                                    {{ $action->label }}
                                                </span>
                                                <span
                                                    class="{{ $severityClass }} rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                                >
                                                    {{ $severityLabel }}
                                                </span>
                                            </div>
                                            <p
                                                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ $action->owner }} ·
                                                {{ $action->sourcePackage }}
                                            </p>
                                        </div>

                                        <a
                                            href="{{ $action->url }}"
                                            aria-label="{{ __('capell-publishing-studio::workflow.action_aria_label', ['action' => $action->nextActionLabel, 'label' => $action->label, 'count' => $action->count]) }}"
                                            class="text-primary-600 hover:text-primary-500 focus-visible:outline-primary-600 dark:text-primary-400 dark:hover:text-primary-300 inline-flex shrink-0 items-center gap-2 rounded-md px-2.5 py-1.5 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                                            aria-label="{{ __('capell-publishing-studio::workflow.action_aria_label', ['action' => $action->nextActionLabel, 'label' => $action->label, 'count' => $action->count]) }}"
                                        >
                                            <span
                                                class="rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-white/10 dark:text-gray-300"
                                            >
                                                {{ $action->count }}
                                            </span>
                                            {{ $action->nextActionLabel }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
