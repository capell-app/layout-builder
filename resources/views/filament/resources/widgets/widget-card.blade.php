@php
    use Capell\LayoutBuilder\Actions\ResolveWidgetPickerPreviewAction;
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
        $preview = ResolveWidgetPickerPreviewAction::run($record);
    @endphp

    <article
        @class([
            'group overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 transition duration-150 hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-white/10',
            'opacity-70' => $preview->disabled,
        ])
        aria-disabled="{{ $preview->disabled ? 'true' : 'false' }}"
    >
        <div
            class="relative aspect-[16/10] w-full overflow-hidden bg-gray-100 dark:bg-gray-950"
        >
            @if ($preview->imageUrl !== null)
                <img
                    src="{{ $preview->imageUrl }}"
                    alt="{{ __('capell-layout-builder::table.widget_preview_alt', ['widget' => $preview->name]) }}"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    loading="lazy"
                />
            @else
                <div
                    role="img"
                    aria-label="{{ __('capell-layout-builder::table.widget_icon_alt', ['widget' => $preview->name]) }}"
                    class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-500),0.18),transparent_34%),linear-gradient(135deg,rgba(17,24,39,0.04),rgba(17,24,39,0.12))] px-6 text-center dark:bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-400),0.22),transparent_34%),linear-gradient(135deg,rgba(255,255,255,0.06),rgba(255,255,255,0.02))]"
                >
                    <x-filament::icon
                        :icon="$preview->icon"
                        class="h-16 w-16 text-gray-500 dark:text-gray-400"
                        aria-hidden="true"
                    />
                </div>
            @endif
        </div>

        <div class="space-y-3 p-4">
            <div class="min-w-0">
                <h3
                    class="truncate text-base font-semibold text-gray-950 dark:text-white"
                >
                    {{ $preview->name }}
                </h3>

                @if ($preview->disabled)
                    <span
                        class="mt-1 inline-flex text-xs font-medium text-danger-600 dark:text-danger-400"
                    >
                        {{ __('capell-admin::generic.disabled') }}
                    </span>
                @endif
            </div>
        </div>
    </article>
@endif
