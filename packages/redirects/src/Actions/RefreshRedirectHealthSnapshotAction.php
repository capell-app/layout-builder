<?php

declare(strict_types=1);

namespace Capell\Redirects\Actions;

use Capell\Core\Models\PageUrl;
use Capell\Redirects\Models\RedirectHealthSnapshot;
use Lorisleiva\Actions\Concerns\AsAction;

class RefreshRedirectHealthSnapshotAction
{
    use AsAction;

    private const CHAIN_TARGET_PLACEHOLDER = '__CAPELL_REDIRECT_CHAIN_TARGET__';

    public function handle(PageUrl $redirect): RedirectHealthSnapshot
    {
        $errors = [];
        $warnings = [];

        if ($redirect->target_url !== null && $redirect->target_url !== '') {
            $result = ValidateRedirectAction::run(
                sourceUrl: $redirect->url,
                targetUrl: $redirect->target_url,
                siteId: $redirect->site_id,
                languageId: $redirect->language_id,
                excludeId: $redirect->id,
                statusCode: $redirect->status_code?->value,
                validateDuplicateSource: false,
            );

            $errors = $result['errors'];
            $warnings = $result['warnings'];
        }

        return RedirectHealthSnapshot::query()->updateOrCreate(
            ['page_url_id' => $redirect->id],
            [
                'source_url' => $redirect->url,
                'target_url' => $redirect->target_url,
                'has_chain' => $this->hasChainWarning($warnings),
                'has_loop' => in_array(__('redirects::message.redirect_loop_detected'), $errors, true),
                'warning_count' => count($warnings),
                'error_count' => count($errors),
                'computed_at' => now(),
            ],
        );
    }

    /**
     * @param  list<string>  $warnings
     */
    private function hasChainWarning(array $warnings): bool
    {
        $chainWarningMessage = __('redirects::message.redirect_chain_detected', [
            'final_target' => self::CHAIN_TARGET_PLACEHOLDER,
        ]);
        $chainWarningParts = explode(self::CHAIN_TARGET_PLACEHOLDER, $chainWarningMessage, 2);
        $chainWarningPrefix = $chainWarningParts[0] ?? '';
        $chainWarningSuffix = $chainWarningParts[1] ?? '';

        foreach ($warnings as $warning) {
            if (str_starts_with($warning, $chainWarningPrefix) && str_ends_with($warning, $chainWarningSuffix)) {
                return true;
            }
        }

        return false;
    }
}
