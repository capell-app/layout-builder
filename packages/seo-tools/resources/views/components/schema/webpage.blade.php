<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;
use Capell\SeoTools\Actions\PageMetaSchemaAction;

$page = Frontend::page();
$site = Frontend::site();
$language = Frontend::language();

$json = PageMetaSchemaAction::run($page, $site, $language);

?>

{!! '<script type="application/ld+json">' . json_encode($json, JSON_UNESCAPED_SLASHES) . '</script>' !!}
