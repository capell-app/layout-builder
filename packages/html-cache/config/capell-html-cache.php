<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'enabled' => Env::get('CAPELL_HTML_CACHE', true),
    'write_enabled' => Env::get('CAPELL_WRITE_HTML_CACHE', true),
    'minify_html' => Env::get('CAPELL_MINIFY_HTML', true),
    'cache_ttl' => 3600,
    'cache_vary_headers' => ['Accept-Encoding'],
    'cache_skip_authenticated' => true,
    'model_event_registration_mode' => Env::get('CAPELL_MODEL_EVENT_REGISTRATION_MODE', 'deferred'),
    'static_generation' => [
        'internal_requests' => Env::get('CAPELL_STATIC_HTML_INTERNAL_REQUESTS', false),
    ],
    'site_health_public_html_scan_limit' => (int) Env::get('CAPELL_HTML_CACHE_SITE_HEALTH_SCAN_LIMIT', 100),
    'site_health_unindexed_public_html_scan_limit' => (int) Env::get('CAPELL_HTML_CACHE_SITE_HEALTH_UNINDEXED_SCAN_LIMIT', 25),
    'site_health_cached_url_limit' => (int) Env::get('CAPELL_HTML_CACHE_SITE_HEALTH_CACHED_URL_LIMIT', 20),
    'public_html_authoring_markers' => [
        'data-capell-authoring',
        'data-capell-editable',
        'data-capell-editor',
        'data-capell-editor-url',
        'data-field-path',
        'data-model-id',
        'data-permission',
        'data-capell-package',
        '"field_path"',
        '"fieldpath"',
        '"model_id"',
        '"modelid"',
        '"editor_url"',
        '"editorurl"',
        '"signed_editor_url"',
        '"signededitorurl"',
        'capell-authoring',
        'capell-editor',
    ],
];
