@props([
    'previewData',
    'assetsToggleAction' => null,
    'blockActions' => null,
])
@php
    use Capell\Core\Enums\MediaConversionEnum;
    use Filament\Support\Enums\Size;
@endphp

<div
    class="layout-builder-block-preview relative rounded-lg bg-white shadow-sm transition-shadow focus-within:shadow-md hover:shadow-md dark:bg-gray-900"
>
    @if ($previewData->image)
        <div
            class="h-28 overflow-hidden rounded-t-lg bg-gray-100 dark:bg-gray-800"
        >
            {{ $previewData->image->img(MediaConversionEnum::Thumbnail->value)->lazy()->attributes(['class' => 'h-full w-full object-cover']) }}
        </div>
    @endif

    @if ($assetsToggleAction || $blockActions)
        <div
            class="layout-block-preview-actions absolute right-4 top-4 z-10 flex items-center justify-end gap-1"
        >
            @if ($assetsToggleAction)
                {{ $assetsToggleAction }}
            @endif

            @if ($blockActions)
                {{ $blockActions }}
            @endif
        </div>
    @endif

    <div class="space-y-2 p-4 pr-14">
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($previewData->typeLabel)
                <x-filament::badge size="xs" color="info">
                    {{ $previewData->typeLabel }}
                </x-filament::badge>
            @endif

            @if ($previewData->assetCount > 0)
                <x-filament::badge
                    :color="$previewData->hasPageAssets ? 'primary' : 'gray'"
                    :size="Size::ExtraSmall"
                >
                    {{ $previewData->assetCount }}
                </x-filament::badge>
            @endif

            <div
                class="min-w-0 flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white"
            >
                {{ $previewData->title ?: $previewData->label }}
            </div>
        </div>

        @if ($previewData->excerpt)
            <p
                class="line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300"
            >
                {{ $previewData->excerpt }}
            </p>
        @endif
    </div>
</div>
