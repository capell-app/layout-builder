<?php
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Context\FrontendContext;
use Illuminate\Support\Facades\Route;

$site = Frontend::site();
$page = Frontend::page();
$language = Frontend::language();
$theme = Frontend::theme();

$routeName = config('capell-page.frontend.route_name', 'capell-frontend.beacon');
$beaconRoute = is_string($routeName) && Route::has($routeName) ? route($routeName) : null;

$beacon = [
    'url' => $beaconRoute,
    'timeout' => config('session.lifetime') * 60 * 1000,
    'error' => FrontendContext::isErrorPage(),
    'payload' => [],
];
?>

<div wire:ignore>
    <script>
        window.beaconData = @json($beacon)
    </script>
</div>
