<?php

declare(strict_types=1);

namespace Capell\Analytics\Http\Controllers;

use Capell\Analytics\Actions\UpdateAnalyticsConsentAction;
use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Enums\AnalyticsConsentCategory;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnalyticsConsentController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region' => ['required', Rule::enum(AnalyticsConsentRegion::class)],
            'status' => ['required', Rule::enum(AnalyticsConsentStatus::class)],
            'terms_accepted' => ['boolean'],
            'categories.analytics' => ['boolean'],
            'categories.marketing' => ['boolean'],
            'categories.preferences' => ['boolean'],
        ]);

        $region = AnalyticsConsentRegion::from((string) $validated['region']);
        $status = AnalyticsConsentStatus::from((string) $validated['status']);

        if ($status === AnalyticsConsentStatus::Granular && ! $request->boolean('terms_accepted')) {
            throw ValidationException::withMessages([
                'terms_accepted' => __('validation.accepted', ['attribute' => 'terms accepted']),
            ]);
        }

        $consentData = $this->consentDataForStatus($status, $validated);
        $consent = UpdateAnalyticsConsentAction::run($request, $consentData, $status, $region);

        return response()->json([
            'visit_id' => $consent->visit?->uuid,
            'enabled_categories' => array_map(
                static fn (AnalyticsConsentCategory $category): string => $category->value,
                $consent->categories->enabledCategories(),
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function consentDataForStatus(AnalyticsConsentStatus $status, array $validated): AnalyticsConsentData
    {
        if ($status === AnalyticsConsentStatus::AcceptedAll) {
            return new AnalyticsConsentData(
                essential: true,
                analytics: true,
                marketing: true,
                preferences: true,
            );
        }

        if ($status === AnalyticsConsentStatus::RejectedNonEssential) {
            return new AnalyticsConsentData(essential: true);
        }

        return new AnalyticsConsentData(
            essential: true,
            analytics: (bool) data_get($validated, 'categories.analytics', false),
            marketing: (bool) data_get($validated, 'categories.marketing', false),
            preferences: (bool) data_get($validated, 'categories.preferences', false),
        );
    }
}
