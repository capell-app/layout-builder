<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Redirects\Contracts\NullRedirectResolver;

it('returns no redirect decision', function (): void {
    $resolver = new NullRedirectResolver;

    expect($resolver->resolve(new Site, new Language, '/old'))->toBeNull();
});
