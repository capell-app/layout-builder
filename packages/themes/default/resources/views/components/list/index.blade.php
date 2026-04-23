<?php

declare(strict_types=1);

?>

@props(['item', 'gap' => 'gap-y-1.5'])
<ul
    {{ $attributes->class(['list-items flex list-disc flex-col pl-4', $gap]) }}
>
    {{ $slot }}
</ul>
