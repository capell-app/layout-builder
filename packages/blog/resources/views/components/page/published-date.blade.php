<?php

declare(strict_types=1);

?>

@props(['date'])
<time
    datetime="{{ $date->toW3cString() }}"
    {{ $attributes->class('published-date text-sm font-medium leading-none tracking-tight text-gray-400') }}
>
    {{ __('capell-frontend::generic.visible_from', ['date' => $date->format(config('capell-frontend.date_format'))]) }}
</time>

<?php
