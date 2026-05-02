<?php
use Capell\Frontend\Facades\Frontend;
use Capell\SeoTools\Actions\SchemaGraphAction;

$page = Frontend::page();
$site = Frontend::site();
$language = Frontend::language();

$graphData = SchemaGraphAction::run($page, $site, $language);

?>

{!! $graphData->toJsonLdScript() !!}
