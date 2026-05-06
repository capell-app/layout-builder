@if (isset($curatedMedia))
    @php
        $curatedStyle = "background-image: url('{$curatedMedia['url']}'); width: {$curatedMedia['width']}px; height: {$curatedMedia['height']}px;";
        $curatedAttributeStyle = $attributes->get('style');

        if (is_scalar($curatedAttributeStyle) && (string) $curatedAttributeStyle !== '') {
            $curatedStyle .= ' ' . rtrim((string) $curatedAttributeStyle, ';') . ';';
        }
    @endphp

    <div
        alt="{{ $media['alt'] }}"
        style="{{ $curatedStyle }}"
        {{ $attributes->except('style') }}
    ></div>
@elseif ($media)
    @php
        $mediaWidth = $width && $height ? $width : ($media->getWidth() ?? 0);
        $mediaHeight = $width && $height ? $height : ($media->getHeight() ?? 0);
        $mediaStyle = "background-image: url('{$media->getUrl()}'); width: {$mediaWidth}px; height: {$mediaHeight}px;";
        $mediaAttributeStyle = $attributes->get('style');

        if (is_scalar($mediaAttributeStyle) && (string) $mediaAttributeStyle !== '') {
            $mediaStyle .= ' ' . rtrim((string) $mediaAttributeStyle, ';') . ';';
        }
    @endphp

    <div
        style="{{ $mediaStyle }}"
        {{ $attributes->except(['alt', 'width', 'height', 'style']) }}
    >
        <span class="sr-only">
            {{ $attributes->get('alt', $media->getCustomProperty('alt', $media->getName())) }}
        </span>
    </div>
@endif
