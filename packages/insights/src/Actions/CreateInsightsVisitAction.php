<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateInsightsVisitAction
{
    use AsAction;

    public function handle(Request $request, InsightsConsentRegion $region): InsightsVisit
    {
        $referer = $request->headers->get('referer');

        return InsightsVisit::query()->create([
            'uuid' => (string) Str::uuid(),
            'site_id' => $this->integerInput($request, 'site_id'),
            'language_id' => $this->integerInput($request, 'language_id'),
            'consent_region' => $region,
            'consent_status' => InsightsConsentStatus::Pending,
            'landing_url' => $referer ?? $request->fullUrl(),
            'referrer_url' => $referer,
            'utm_source' => $this->stringInput($request, 'utm_source'),
            'utm_medium' => $this->stringInput($request, 'utm_medium'),
            'utm_campaign' => $this->stringInput($request, 'utm_campaign'),
            'ip_hash' => $this->hashVisitorValue($request->ip()),
            'user_agent_hash' => $this->hashVisitorValue($request->userAgent()),
            'started_at' => now()->toImmutable(),
            'last_seen_at' => now()->toImmutable(),
        ]);
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

    private function integerInput(Request $request, string $key): ?int
    {
        $value = $request->input($key);

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function stringInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    private function hashSalt(): string
    {
        $salt = config('capell-insights.hash_salt', 'capell-insights');

        return is_string($salt) && $salt !== '' ? $salt : 'capell-insights';
    }
}
