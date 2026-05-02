<?php
use Capell\Frontend\Facades\Frontend;
use Capell\SeoTools\Actions\BreadcrumbsSchemaAction;

$page = Frontend::page();
$site = Frontend::site();
$language = Frontend::language();

$json = BreadcrumbsSchemaAction::run($page, $site, $language);
$jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

?>

{!! '<script type="application/ld+json">' . json_encode($json, $jsonFlags) . '</script>' !!}
