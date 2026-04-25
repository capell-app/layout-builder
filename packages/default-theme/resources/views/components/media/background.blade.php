<?php

declare(strict_types=1);

?>

@if (isset($curatedMedia))
    <div
        alt="{{ $media['alt'] }}"
        style="background-image: url('{{ $curatedMedia['url'] }}')"
        style="
            width: {{ $curatedMedia['width'] }}px;
            height: {{ $curatedMedia['height'] }}px;
        "
        {{ $attributes }}
    ></div>
@elseif ($media)
    <div
        style="background-image: url('{{ $media->getUrl() }}')"
        @if ($width && $height)
            style="width: {{ $width }}px; height: {{ $height }}px;"
        @else
            style="width: {{ $media->getWidth() ?? 0 }}px; height: {{ $media->getHeight() ?? 0 }}px;"
        @endif
        {{ $attributes->except(['alt', 'width', 'height']) }}
    >
        <span class="sr-only">
            {{ $attributes->get('alt', $media->getCustomProperty('alt', $media->getName())) }}
        </span>
    </div>
@endif

<?php
