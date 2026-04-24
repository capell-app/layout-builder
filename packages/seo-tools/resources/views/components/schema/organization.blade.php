<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;
use Capell\SeoTools\Actions\SiteMetaSchemaAction;

$site = Frontend::site();
$language = Frontend::language();

$json = SiteMetaSchemaAction::run($site, $language);

?>

{!! '<script type="application/ld+json">' . json_encode($json, JSON_UNESCAPED_SLASHES) . '</script>' !!}
