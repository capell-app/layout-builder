@props([
    'src',
    'alt' => '',
    'width' => 1200,
    'height' => 800,
    'sizes' => '(min-width: 1024px) 50vw, 100vw',
    'lazy' => true,
    'builder' => null,
])

@php
    use Capell\Themes\Core\Images\ResponsiveImage;

    $responsive = $builder ?? new ResponsiveImage;
@endphp

{!! $responsive->render($src, $alt, (int) $width, (int) $height, $sizes, (bool) $lazy) !!}
