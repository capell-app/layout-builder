<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\SuggestMetaDescriptionsAction;
use Capell\SeoTools\Support\Context\ContentActionContext;
use Capell\SeoTools\Support\PrismProvider;
use Capell\Tests\Assistant\Fixtures\FakeOpenAIProviderForDescriptions;

uses()->group('admin-ai');

it('does not include page_id/language_id columns when sentinel IDs are used', function (): void {
    app()->bind(PrismProvider::class, fn (): FakeOpenAIProviderForDescriptions => new FakeOpenAIProviderForDescriptions);

    $context = new ContentActionContext(content: 'Example content', keywords: 'keywords');
    $result = SuggestMetaDescriptionsAction::run($context);

    expect($result)->toBeArray()->and(count($result))->toBeGreaterThan(0);
});
