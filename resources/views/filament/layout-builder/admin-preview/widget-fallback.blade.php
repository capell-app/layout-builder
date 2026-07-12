@props ([
    'previewData',
])

<div class="clb-preview-widget">
    <div class="clb-preview-widget-body">
        <div class="clb-preview-widget-icon">
            @svg ($previewData->icon ?: 'heroicon-o-cube', 'h-5 w-5')
        </div>

        <div>
            @if ($previewData->typeLabel)
                <div class="clb-preview-widget-type">
                    {{ $previewData->typeLabel }}
                </div>
            @endif

            <h2>{{ $previewData->title ?: $previewData->label }}</h2>

            @if ($previewData->excerpt)
                <p>{{ $previewData->excerpt }}</p>
            @endif
        </div>
    </div>
</div>
