<?php

declare(strict_types=1);

use Capell\Campaigns\Actions\BuildCampaignUrlAction;
use Capell\Campaigns\Data\UtmData;

it('adds missing utm parameters while preserving existing query values', function (): void {
    $url = BuildCampaignUrlAction::run(
        '/signup?utm_source=existing&plan=pro#pricing',
        new UtmData(
            source: 'newsletter',
            medium: 'email',
            campaign: 'spring-launch',
            content: 'hero',
        ),
    );

    expect($url)->toBe('/signup?utm_source=existing&plan=pro&utm_medium=email&utm_campaign=spring-launch&utm_content=hero#pricing');
});

it('returns the original url when no utm values are present', function (): void {
    expect(BuildCampaignUrlAction::run('/signup', new UtmData))->toBe('/signup');
});
