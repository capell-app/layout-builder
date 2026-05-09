<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'tables' => [
        'profiles' => 'email_profiles',
        'templates' => 'email_templates',
        'template_variants' => 'email_template_variants',
        'messages' => 'email_messages',
        'recipients' => 'email_recipients',
        'events' => 'email_events',
        'replies' => 'email_replies',
        'suppressions' => 'email_suppressions',
        'template_registrations' => 'email_template_registrations',
        'tracking_tokens' => 'email_tracking_tokens',
    ],
    'default_provider' => 'smtp',
    'queue' => Env::get('CAPELL_EMAIL_STUDIO_QUEUE', 'default'),
    'track_opens' => true,
    'track_clicks' => true,
    'body_retention_days' => 90,
    'webhook_tolerance_seconds' => 300,
    'public_route_prefix' => Env::get('CAPELL_EMAIL_STUDIO_PUBLIC_PREFIX', 'mail'),
    'tracking_token_ttl_days' => 180,
    'webhook_rate_limit' => 'email-studio-webhooks',
    'tracking_rate_limit' => 'email-studio-tracking',
];
