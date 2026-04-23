<?php

declare(strict_types=1);

?>

<span
    {{ $attributes->class(['text-primary whitespace-normal break-words text-2xl font-bold leading-tight tracking-tight xl:text-3xl']) }}
>
    {{ $slot }}
</span>

<?php
