@php
    use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
    use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
    use Illuminate\Support\Str;

    /** @var LayoutBulkChangeRun|null $run */
    $summary = $run?->summary ?? [];
    $results = $run?->results ?? collect();
@endphp

@if (! $run instanceof LayoutBulkChangeRun)
    <div class="text-sm text-gray-600 dark:text-gray-300">
        {{ __('capell-layout-builder::message.bulk_change_preview_missing') }}
    </div>
@else
    <div class="space-y-5">
        <dl class="grid grid-cols-2 gap-3 md:grid-cols-6">
            @foreach ([
                          'target_layouts' => __('capell-layout-builder::generic.layouts'),
                          'target_pages' => __('capell-layout-builder::generic.pages'),
                          'changed_layouts' => __('capell-layout-builder::generic.changed'),
                          'blocked_layouts' => __('capell-layout-builder::generic.blocked'),
                          'skipped_layouts' => __('capell-layout-builder::generic.skipped'),
                      ] as $key => $label)
                <div>
                    <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        {{ $label }}
                    </dt>
                    <dd
                        class="text-lg font-semibold text-gray-950 dark:text-white"
                    >
                        {{ $summary[$key] ?? 0 }}
                    </dd>
                </div>
            @endforeach

            <div>
                <dt
                    class="text-xs font-medium text-gray-500 dark:text-gray-400"
                >
                    {{ __('capell-layout-builder::generic.status') }}
                </dt>
                <dd class="text-lg font-semibold text-gray-950 dark:text-white">
                    {{ Str::headline($run->status->value) }}
                </dd>
            </div>
        </dl>

        <div
            class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10"
        >
            <table
                class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10"
            >
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.layout') }}
                        </th>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.pages') }}
                        </th>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.status') }}
                        </th>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.before') }}
                        </th>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.after') }}
                        </th>
                        <th
                            class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::generic.notes') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($results as $result)
                        @php
                            $changes = $result->changes ?? [];
                            $warnings = $result->warnings ?? [];
                            $formatDiffSide = fn (string $side): string => collect($changes['container_diffs'] ?? [])
                                ->map(fn (array $diff): string => (string) ($diff['container'] ?? '') . ': ' . implode(', ', $diff[$side] ?? []))
                                ->filter()
                                ->implode(' | ');
                        @endphp

                        <tr>
                            <td
                                class="px-3 py-3 align-top text-gray-950 dark:text-white"
                            >
                                <div class="font-medium">
                                    {{ $result->layout?->name ?? __('capell-layout-builder::generic.unknown') }}
                                </div>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $result->layout?->key }}
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 align-top text-gray-700 dark:text-gray-300"
                            >
                                {{ $result->page_count }}
                            </td>
                            <td
                                class="px-3 py-3 align-top text-gray-700 dark:text-gray-300"
                            >
                                {{ Str::headline($result->status->value) }}
                            </td>
                            <td
                                class="max-w-xs px-3 py-3 align-top text-gray-700 dark:text-gray-300"
                            >
                                {{ $formatDiffSide('before') }}
                            </td>
                            <td
                                class="max-w-xs px-3 py-3 align-top text-gray-700 dark:text-gray-300"
                            >
                                {{ $formatDiffSide('after') }}
                            </td>
                            <td
                                class="px-3 py-3 align-top text-gray-700 dark:text-gray-300"
                            >
                                @if ($result->skipped_reason)
                                    <div>{{ $result->skipped_reason }}</div>
                                @endif

                                @foreach (($changes['messages'] ?? []) as $message)
                                    <div>{{ $message }}</div>
                                @endforeach

                                @foreach ($warnings as $warning)
                                    <div
                                        class="text-danger-600 dark:text-danger-400 font-medium"
                                    >
                                        {{ $warning }}
                                    </div>
                                @endforeach

                                @if ($result->status === LayoutBulkChangeResultStatus::Changed && $warnings === [])
                                    <div>
                                        {{ __('capell-layout-builder::message.bulk_change_ready_to_apply') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
