<?php

declare(strict_types=1);

?>

@props(['date'])
<time
    datetime="{{ $date->toW3cString() }}"
    {{ $attributes->class('published-date text-sm font-medium leading-none text-gray-500') }}
>
    {{ __('capell-frontend::generic.publish_from', ['date' => $date->format(config('capell-frontend.date_format'))]) }}
</time>

<?php
