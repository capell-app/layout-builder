@props([
    'previewData',
    'assetsToggleAction' => null,
    'blockActions' => null,
])
@php
    use Capell\Core\Enums\MediaConversionEnum;
@endphp

<div
    class="layout-builder-block-preview relative rounded-lg bg-white shadow-sm transition-shadow focus-within:shadow-md hover:shadow-md dark:bg-gray-950"
>
    @if ($previewData->image)
        <div class="absolute inset-0 overflow-hidden rounded-lg">
            {{ $previewData->image->img(MediaConversionEnum::Thumbnail->value)->lazy()->attributes(['alt' => '', 'aria-hidden' => 'true', 'class' => 'h-full w-full object-cover opacity-20']) }}
        </div>
    @endif

    @if ($assetsToggleAction || $blockActions)
        <div
            class="layout-block-preview-actions absolute right-5 top-5 z-10 flex items-center justify-end gap-1"
        >
            @if ($assetsToggleAction)
                {{ $assetsToggleAction }}
            @endif

            @if ($blockActions)
                {{ $blockActions }}
            @endif
        </div>
    @endif

    <div class="relative min-h-40 p-5 pr-16 text-gray-950 dark:text-white">
        <div class="mb-3 flex items-start">
            <div
                class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
            >
                {{ __('capell-layout-builder::generic.page_content_block') }}
            </div>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div
                class="min-w-0 max-w-2xl flex-1 text-xl font-semibold leading-tight"
            >
                {{ $previewData->title ?: $previewData->label }}
            </div>
        </div>

        @if ($previewData->excerpt)
            <p
                class="mt-3 line-clamp-3 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300"
            >
                {{ $previewData->excerpt }}
            </p>
        @endif
    </div>
</div>
