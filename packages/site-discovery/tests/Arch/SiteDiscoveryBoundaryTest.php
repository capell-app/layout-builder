<?php

declare(strict_types=1);

arch('site-discovery does not import consumer packages')
    ->expect('Capell\SiteDiscovery')
    ->not->toUse([
        'Capell\Blog',
        'Capell\SeoSuite',
        'Capell\Search',
        'Capell\Tags',
        'Capell\PublishingStudio',
    ]);

arch()
    ->expect('Capell\SiteDiscovery')
    ->classes()
    ->toUseStrictEquality();
