<?php

declare(strict_types=1);

?>

<div
    class="no-results font-semibold tracking-tight text-gray-600 dark:text-gray-400"
>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        {{ __('capell-frontend::generic.no_results') }}
    @endif
</div>

<?php
