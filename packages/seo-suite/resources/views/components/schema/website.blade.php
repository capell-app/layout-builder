<?php
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\SeoSuite\Enums\SchemaEntityTypeEnum;

$site = Frontend::site();
$language = Frontend::language();
$siteUrl = $site->siteDomain->full_url;

$json = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => SchemaEntityTypeEnum::WebSite->toId($siteUrl),
    'name' => $site->getMeta('business_name', $site->translation->title),
    'url' => $siteUrl,
];

$searchPage = Page::getFirstPageByTypeForSite('results', $site, $language);

if ($searchPage?->pageUrl?->full_url !== null && $searchPage?->pageUrl?->full_url !== '') {
    $json['potentialAction'] = [
        '@type' => 'SearchAction',
        'target' => [
            '@type' => 'EntryPoint',
            'urlTemplate' => $searchPage->pageUrl->full_url . '?q={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
    ];
}

$jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

?>

{!! '<script type="application/ld+json">' . json_encode($json, $jsonFlags) . '</script>' !!}
