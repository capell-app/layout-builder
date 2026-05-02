<?php

declare(strict_types=1);

use Capell\SiteSearch\Actions\NormalizeSearchQueryAction;

test('normalizes whitespace and lowercases the search query', function (): void {
    $query = NormalizeSearchQueryAction::run("  Laravel\tSearch\nPackage  ");

    expect($query)->toBe('laravel search package');
});

test('returns an empty string for whitespace only queries', function (): void {
    $query = NormalizeSearchQueryAction::run(" \n\t ");

    expect($query)->toBe('');
});
