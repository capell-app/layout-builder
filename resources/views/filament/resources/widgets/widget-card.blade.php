@php
    use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
    use Capell\LayoutBuilder\Models\Widget;

    $record = $getRecord();
@endphp

@once
    <style>
        .capell-layout-builder-widget-selection-card-record {
            padding: 0;
            position: relative;
        }

        .capell-layout-builder-widget-selection-card-record.fi-ta-record-with-content-prefix {
            grid-template-columns: minmax(0, 1fr);
        }

        .capell-layout-builder-widget-selection-card-record > .fi-ta-record-checkbox {
            position: absolute;
            inset-inline-start: 1rem;
            top: 1rem;
            z-index: 20;
        }

        .capell-layout-builder-widget-selection-card-record > .fi-ta-record-content-ctn {
            min-width: 0;
            padding: 0;
        }

        .fi-ta-content-ctn .fi-ta-content .capell-layout-builder-widget-selection-card-record > .fi-ta-record-content-ctn .fi-ta-record-content {
            padding: 0;
        }
    </style>
@endonce

@if ($record instanceof Widget)
    @php
        $widgetName = trim((string) $record->getAttribute('name'));
        $widgetName = $widgetName !== '' ? $widgetName : (string) __('capell-layout-builder::generic.widget');
        $previewUrl = GetLayoutPreviewImageUrlAction::run($record);
        $assetCount = (int) ($record->getAttribute('widget_assets_count') ?? 0);
        $layoutCount = (int) ($record->getAttribute('layouts_count') ?? 0);
    @endphp

    <article
        @class([
            'group overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 transition duration-150 hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-white/10',
            'opacity-70' => $record->status === false,
        ])
        aria-disabled="{{ $record->status === false ? 'true' : 'false' }}"
    >
        <div
            class="relative aspect-[16/10] w-full overflow-hidden bg-gray-100 dark:bg-gray-950"
        >
            @if (is_string($previewUrl) && $previewUrl !== '')
                <img
                    src="{{ $previewUrl }}"
                    alt="{{ __('capell-layout-builder::table.widget_preview_alt', ['widget' => $widgetName]) }}"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    loading="lazy"
                />
            @else
                <div
                    role="img"
                    aria-label="{{ __('capell-layout-builder::table.widget_preview_fallback') }}"
                    class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-500),0.18),transparent_34%),linear-gradient(135deg,rgba(17,24,39,0.04),rgba(17,24,39,0.12))] px-6 text-center dark:bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-400),0.22),transparent_34%),linear-gradient(135deg,rgba(255,255,255,0.06),rgba(255,255,255,0.02))]"
                >
                    <span
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::table.widget_preview_fallback') }}
                    </span>
                </div>
            @endif
        </div>

        <div class="space-y-3 p-4">
            <div class="min-w-0">
                <h3
                    class="truncate text-base font-semibold text-gray-950 dark:text-white"
                >
                    {{ $widgetName }}
                </h3>

                @if ($record->status === false)
                    <span
                        class="mt-1 inline-flex text-xs font-medium text-danger-600 dark:text-danger-400"
                    >
                        {{ __('capell-admin::generic.disabled') }}
                    </span>
                @endif
            </div>

            <ul
                aria-label="{{ __('capell-layout-builder::table.usage') }}"
                class="flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300"
            >
                <li class="rounded-md bg-gray-50 px-2.5 py-1.5 dark:bg-white/5">
                    {{ trans_choice('capell-layout-builder::table.widget_usage_layouts', $layoutCount, ['count' => $layoutCount]) }}
                </li>
                <li class="rounded-md bg-gray-50 px-2.5 py-1.5 dark:bg-white/5">
                    {{ trans_choice('capell-layout-builder::message.layout_tree_asset_count', $assetCount, ['count' => $assetCount]) }}
                </li>
            </ul>
        </div>
    </article>
@endif
