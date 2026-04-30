<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateAnalyticsVisitAction
{
    use AsAction;

    public function handle(Request $request, AnalyticsConsentRegion $region): AnalyticsVisit
    {
        return AnalyticsVisit::query()->create([
            'uuid' => (string) Str::uuid(),
            'site_id' => $this->integerInput($request, 'site_id'),
            'language_id' => $this->integerInput($request, 'language_id'),
            'consent_region' => $region,
            'consent_status' => AnalyticsConsentStatus::Pending,
            'landing_url' => $request->headers->get('referer') ?: $request->fullUrl(),
            'referrer_url' => $request->headers->get('referer'),
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
        if (! (bool) config('capell-analytics.hash_visitor_data', true)) {
            return null;
        }

        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('capell-analytics.hash_salt', 'capell-analytics'));
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
}
