<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateAnalyticsConsentAction
{
    use AsAction;

    public function handle(
        Request $request,
        AnalyticsConsentData $data,
        AnalyticsConsentStatus $status,
        AnalyticsConsentRegion $region,
    ): AnalyticsConsent {
        if ($status === AnalyticsConsentStatus::Granular && ! $request->boolean('terms_accepted')) {
            throw ValidationException::withMessages([
                'terms_accepted' => __('validation.accepted', ['attribute' => 'terms accepted']),
            ]);
        }

        $visit = $this->resolveVisit($request, $region);
        $acceptedTerms = $request->boolean('terms_accepted');

        $consent = AnalyticsConsent::query()->create([
            'visit_id' => $visit->getKey(),
            'consent_region' => $region,
            'status' => $status,
            'categories' => $data,
            'policy_version' => $this->policyVersion(),
            'terms_accepted_at' => $acceptedTerms ? now()->toImmutable() : null,
            'decided_at' => now()->toImmutable(),
            'ip_hash' => $this->hashVisitorValue($request->ip()),
            'user_agent_hash' => $this->hashVisitorValue($request->userAgent()),
        ]);

        $visit->forceFill([
            'consent_region' => $region,
            'consent_status' => $status,
            'last_seen_at' => now()->toImmutable(),
        ])->save();

        Cookie::queue('capell_analytics_visit', $visit->uuid, 60 * 24 * 365);

        return $consent->load('visit');
    }

    private function resolveVisit(Request $request, AnalyticsConsentRegion $region): AnalyticsVisit
    {
        $visitUuid = $request->cookie('capell_analytics_visit');

        if (is_string($visitUuid) && $visitUuid !== '') {
            $visit = AnalyticsVisit::query()
                ->where('uuid', $visitUuid)
                ->first();

            if ($visit instanceof AnalyticsVisit) {
                return $visit;
            }
        }

        return CreateAnalyticsVisitAction::run($request, $region);
    }

    private function hashVisitorValue(?string $value): ?string
    {
        if (config('capell-analytics.hash_visitor_data', true) !== true) {
            return null;
        }

        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', $value, $this->hashSalt());
    }

    private function policyVersion(): string
    {
        $policyVersion = config('capell-analytics.policy_version', '1.0');

        return is_string($policyVersion) && $policyVersion !== '' ? $policyVersion : '1.0';
    }

    private function hashSalt(): string
    {
        $salt = config('capell-analytics.hash_salt', 'capell-analytics');

        return is_string($salt) && $salt !== '' ? $salt : 'capell-analytics';
    }
}
