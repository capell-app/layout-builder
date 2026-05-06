<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateInsightsConsentAction
{
    use AsAction;

    public function handle(
        Request $request,
        InsightsConsentData $data,
        InsightsConsentStatus $status,
        InsightsConsentRegion $region,
    ): InsightsConsent {
        if ($status === InsightsConsentStatus::Granular && ! $request->boolean('terms_accepted')) {
            throw ValidationException::withMessages([
                'terms_accepted' => __('validation.accepted', ['attribute' => 'terms accepted']),
            ]);
        }

        $visit = $this->resolveVisit($request, $region);
        $acceptedTerms = $request->boolean('terms_accepted');

        $consent = InsightsConsent::query()->create([
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

        Cookie::queue('capell_insights_visit', $visit->uuid, 60 * 24 * 365);

        return $consent->load('visit');
    }

    private function resolveVisit(Request $request, InsightsConsentRegion $region): InsightsVisit
    {
        $visitUuid = $request->cookie('capell_insights_visit');

        if (is_string($visitUuid) && $visitUuid !== '') {
            $visit = InsightsVisit::query()
                ->where('uuid', $visitUuid)
                ->first();

            if ($visit instanceof InsightsVisit) {
                return $visit;
            }
        }

        return CreateInsightsVisitAction::run($request, $region);
    }

    private function hashVisitorValue(?string $value): ?string
    {
        if (config('capell-insights.hash_visitor_data', true) !== true) {
            return null;
        }

        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', $value, $this->hashSalt());
    }

    private function policyVersion(): string
    {
        $policyVersion = config('capell-insights.policy_version', '1.0');

        return is_string($policyVersion) && $policyVersion !== '' ? $policyVersion : '1.0';
    }

    private function hashSalt(): string
    {
        $salt = config('capell-insights.hash_salt', 'capell-insights');

        return is_string($salt) && $salt !== '' ? $salt : 'capell-insights';
    }
}
