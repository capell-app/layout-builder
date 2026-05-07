<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Adapters\CapellFrontendThemePageAdapter;
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
