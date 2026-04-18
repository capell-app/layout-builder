<?php

declare(strict_types=1);

use Capell\Assistant\Contracts\ContentTargetContract;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Support\ContentTargetResolver;

function makeTarget(string $handle): ContentTargetContract
{
    return new class($handle) implements ContentTargetContract
    {
        public function __construct(private readonly string $handle) {}

        public function apply(array $sections, AiCreatorSession $session): void {}

        public function handles(): string
        {
            return $this->handle;
        }
    };
}

it('resolves a registered target by handle', function (): void {
    $resolver = new ContentTargetResolver;
    $target = makeTarget('flat_json');
    $resolver->register($target);

    expect($resolver->resolve('flat_json'))->toBe($target);
});

it('returns null for an unknown handle', function (): void {
    $resolver = new ContentTargetResolver;

    expect($resolver->resolve('unknown'))->toBeNull();
});

it('preferred returns the last registered target', function (): void {
    $resolver = new ContentTargetResolver;
    $first = makeTarget('flat_json');
    $second = makeTarget('mosaic');
    $resolver->register($first);
    $resolver->register($second);

    expect($resolver->preferred())->toBe($second);
});

it('preferred returns null when no targets registered', function (): void {
    $resolver = new ContentTargetResolver;

    expect($resolver->preferred())->toBeNull();
});

it('all returns every registered target keyed by handle', function (): void {
    $resolver = new ContentTargetResolver;
    $resolver->register(makeTarget('flat_json'));
    $resolver->register(makeTarget('mosaic'));

    expect($resolver->all())->toHaveKeys(['flat_json', 'mosaic']);
});
