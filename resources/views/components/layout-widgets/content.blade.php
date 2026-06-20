@php
    use Capell\Core\Contracts\Media\MediaContract;
    use Capell\Frontend\Actions\GetPageVariablesAction;
    use Capell\Frontend\Actions\RenderHtmlContentAction;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\View\PublicModelMeta;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'content' => '',
    'media' => '',
    'mediaAlign' => $media['align'] ?? null,
    'mediaId' => '',
    'mediaOrdering' => $media['ordering'] ?? 'before',
    'mediaType' => '',
    'page' => $page,
    'theme' => $theme,
    'title' => '',
    'titleColorScheme' => $titleMeta['color'] ?? null,
    'titleHeadingSize' => $titleMeta['heading_size'] ?? null,
    'titleMeta' => [],
    'titleSize' => $titleMeta['size'] ?? null,
])

@php
    $mediaOptions = is_array($media) ? $media : [];
    $resolvedMedia = null;
    $pageVariables = GetPageVariablesAction::run($page);
    $roundedImages = (bool) PublicModelMeta::get($theme, 'rounded_images', false);

    if ($media instanceof MediaContract) {
        $resolvedMedia = $media;
    } elseif ($mediaType === 'page_image' && $page->relationLoaded('image') && $page->image) {
        $resolvedMedia = $page->image;
    }

    // top/bottom align renders the media full width above/below the copy;
    // left/right renders it at one third, floated beside the copy and
    // responsive (full width on small screens, one third from md up).
    $isSideAligned = in_array($mediaAlign, ['left', 'right'], true);

    if ($mediaAlign === 'top') {
        $mediaOrdering = 'before';
    } elseif ($mediaAlign === 'bottom') {
        $mediaOrdering = 'after';
    }

    $mediaWidthClass = $isSideAligned ? 'w-full md:w-1/3' : 'w-full';
@endphp

<div class="capell-component capell-widgets-content">
    @if ($resolvedMedia && $mediaOrdering === 'before')
        <x-capell::media
            :lightbox="$mediaOptions['lightbox'] ?? null"
            :media="$resolvedMedia"
            :media-type="$mediaType"
            :size="$mediaOptions['size'] ?? 'md'"
            :alt="$title"
            fit="crop-center"
            @class([
                'object-cover mb-4',
                $mediaWidthClass,
                'rounded' => $roundedImages,
                'mx-auto' => $mediaAlign === 'center',
                'md:float-right md:ml-10' => $mediaAlign === 'right',
                'md:float-left md:mr-10' => $mediaAlign === 'left',
            ])
        />
    @endif

    @if ($title)
        @php($titleTag = $titleHeadingSize ?: 'div')
        <{{ $titleTag }}>
            {{ __($title, $pageVariables) }}
        </{{ $titleTag }}>
    @endif

    {!! RenderHtmlContentAction::run((string) $content, $pageVariables) !!}

    @if ($resolvedMedia && $mediaOrdering === 'after')
        <x-capell::media
            :lightbox="$mediaOptions['lightbox'] ?? null"
            :media="$resolvedMedia"
            :media-type="$mediaType"
            :size="$mediaOptions['size'] ?? 'md'"
            :alt="$title"
            fit="crop-center"
            @class([
                'object-cover mt-6 mb-10',
                $mediaWidthClass,
                'rounded' => $roundedImages,
                'mx-auto' => $mediaAlign === 'center',
                'md:float-right md:ml-10' => $mediaAlign === 'right',
                'md:float-left md:mr-10' => $mediaAlign === 'left',
            ])
        />
    @endif

    @if ($resolvedMedia && ($mediaAlign === 'left' || $mediaAlign === 'right'))
        <div class="clear-both"></div>
    @endif
</div>
