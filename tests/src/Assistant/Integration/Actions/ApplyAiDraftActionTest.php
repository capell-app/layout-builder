<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\ApplyAiDraftAction;
use Capell\SeoTools\Support\Context\ContentActionContext;

uses()->group('admin-ai');

it('applies a draft and dispatches event', function (): void {
    // Target with a public content property to satisfy the action contract
    $target = new class
    {
        public string $content = 'Old content';

        public function save(): bool
        {
            return true;
        }
    };

    $context = new ContentActionContext(content: 'New improved content');

    $applied = ApplyAiDraftAction::run($context, ['target' => $target]);

    expect($applied)->toBeTrue()
        ->and($target->content)->toBe('New improved content');
});
