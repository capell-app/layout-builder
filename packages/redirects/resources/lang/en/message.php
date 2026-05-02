<?php

declare(strict_types=1);

return [
    'redirect_auto_conflict' => 'An auto-generated redirect already exists for this source URL.',
    'redirect_chain_detected' => 'This redirect creates a chain. Consider pointing directly to :final_target instead.',
    'redirect_duplicate_source' => 'A redirect with this source URL already exists.',
    'redirect_export_complete' => ':count redirect(s) exported successfully.',
    'redirect_import_complete' => ':imported redirect(s) imported, :skipped skipped.',
    'redirect_invalid_status_code' => 'Invalid status code. Only 301 and 302 are supported.',
    'redirect_loop_detected' => 'This redirect creates a loop.',
    'redirect_self_redirect' => 'Source and target URLs cannot be the same.',
    'redirect_source_empty' => 'Source URL is required.',
    'redirect_source_must_start_with_slash' => "Source URL must start with '/'.",
    'redirect_target_empty' => 'Target URL is required.',
    'redirect_target_invalid' => 'Target URL must be a relative path or an http(s) URL.',
    'site_language_not_accessible' => 'The selected language is not attached to the selected site.',
];
