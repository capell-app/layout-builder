@props([
    'color' => '',
    'headingSize' => 'h2',
    'size' => '',
    'title' => null,
])

<?php

use Capell\Core\Contracts\Pageable;
use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
$theme = Frontend::theme();
$translation = $page instanceof Pageable && method_exists($page, 'relationLoaded') && $page->relationLoaded('translation') ? $page->translation : null;

$resolvedTitle = $title ?? ($translation->title ?? '');
?>

<{{ $headingSize }}
    class="capell-component capell-widgets-title block-heading"
>
    {{ $resolvedTitle }}
</{{ $headingSize }}>
