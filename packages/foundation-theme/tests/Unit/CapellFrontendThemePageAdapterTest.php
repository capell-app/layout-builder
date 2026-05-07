<?php

declare(strict_types=1);

use Capell\Core\Models\Translation;
use Capell\ThemeStudio\Core\Adapters\CapellFrontendThemePageAdapter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

it('uses already loaded media without lazy loading named media relations', function (): void {
    $model = new class extends Model
    {
        public function image(): mixed
        {
            throw new RuntimeException('Lazy image relation was loaded.');
        }
    };

    $model->setRelation('media', new Collection([
        new class
        {
            public function getUrl(): string
            {
                return '/storage/example.jpg';
            }
        },
    ]));

    $method = new ReflectionMethod(CapellFrontendThemePageAdapter::class, 'mediaUrl');
    $url = $method->invoke(new CapellFrontendThemePageAdapter, $model);

    expect($url)->toBe('/storage/example.jpg');
});

it('maps public asset presentation data for theme listings', function (): void {
    config()->set('capell-frontend.date_format', 'F j, Y');

    $asset = new class extends Model {};
    $asset->setRawAttributes([
        'name' => 'snowy-owl',
        'created_at' => CarbonImmutable::parse('2026-05-01 10:00:00'),
        'meta' => ['category' => 'Bird profile', 'component' => 'internal-component'],
    ]);

    $translation = new Translation;
    $translation->setRawAttributes([
        'title' => 'Snowy Owl',
        'content' => '<p>A quiet Arctic hunter with bright white plumage.</p>',
    ]);

    $creator = new class extends Model {};
    $creator->setRawAttributes(['name' => 'Ben Johnson']);

    $type = new class extends Model {};
    $type->setRawAttributes(['name' => 'animal profile']);

    $pageUrl = new class extends Model {};
    $pageUrl->setRawAttributes(['url' => '/owls/snowy-owl']);

    $asset->setRelation('translation', $translation);
    $asset->setRelation('creator', $creator);
    $asset->setRelation('type', $type);
    $asset->setRelation('pageUrl', $pageUrl);

    $widgetAsset = new class extends Model {};
    $widgetAsset->setRawAttributes(['meta' => ['badge' => 'Featured']]);
    $widgetAsset->setRelation('asset', $asset);

    $widget = new class extends Model {};
    $widget->setRelation('assets', new Collection([$widgetAsset]));

    $method = new ReflectionMethod(CapellFrontendThemePageAdapter::class, 'assetItems');
    $items = $method->invoke(new CapellFrontendThemePageAdapter, $widget, 'summary');

    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('Snowy Owl')
        ->and($items[0]['url'])->toBe('/owls/snowy-owl')
        ->and($items[0]['publishedDate'])->toBe('May 1, 2026')
        ->and($items[0]['author'])->toBe('Ben Johnson')
        ->and($items[0]['type'])->toBe('Animal Profile')
        ->and($items[0]['meta'])->toBe(['Bird profile', 'Featured'])
        ->and($items[0]['meta'])->not->toContain('internal-component');
});
