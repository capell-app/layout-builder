<?php
use Capell\Frontend\Facades\Frontend;
use Capell\SeoSuite\Actions\SiteMetaSchemaAction;

$site = Frontend::site();
$language = Frontend::language();

$json = SiteMetaSchemaAction::run($site, $language);
$jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

?>

{!! '<script type="application/ld+json">' . json_encode($json, $jsonFlags) . '</script>' !!}
