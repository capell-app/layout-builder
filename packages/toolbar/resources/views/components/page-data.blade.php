<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Context\FrontendContext;

$site = Frontend::site();
$page = Frontend::page();
$language = Frontend::language();
$theme = Frontend::theme();

$routeName = config('capell-page.frontend.route_name', 'capell-frontend.beacon');

$beacon = [
    'url' => route($routeName),
    'timeout' => config('session.lifetime') * 60 * 1000,
    'error' => FrontendContext::isErrorPage(),
    'payload' => [],
];
?>

<div wire:ignore>
    <script>
        window.beaconData = @json($beacon)
    </script>

    <div id="capell-frontend-toolbar"></div>
</div>

<?php
