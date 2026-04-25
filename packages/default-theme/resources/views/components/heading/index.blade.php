<?php

declare(strict_types=1);

?>

@props(['tag' => 'div'])

<{{ $tag }} {{ $attributes }}>{{ $slot }}</{{ $tag }}>
