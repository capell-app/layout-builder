<?php

declare(strict_types=1);

namespace Capell\Insights\Http\Controllers;

use Capell\Insights\Actions\UpdateInsightsConsentAction;
use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Enums\InsightsConsentCategory;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InsightsConsentController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region' => ['required', Rule::enum(InsightsConsentRegion::class)],
            'status' => [
                'required',
                Rule::in([
                    InsightsConsentStatus::AcceptedAll->value,
                    InsightsConsentStatus::RejectedNonEssential->value,
                    InsightsConsentStatus::Granular->value,
                ]),
            ],
            'terms_accepted' => ['boolean'],
            'categories.insights' => ['boolean'],
            'categories.marketing' => ['boolean'],
            'categories.preferences' => ['boolean'],
        ]);

        $region = InsightsConsentRegion::from((string) $validated['region']);
        $status = InsightsConsentStatus::from((string) $validated['status']);

        if ($status === InsightsConsentStatus::Granular && ! $request->boolean('terms_accepted')) {
            throw ValidationException::withMessages([
                'terms_accepted' => __('validation.accepted', ['attribute' => 'terms accepted']),
            ]);
        }

        $consentData = $this->consentDataForStatus($status, $validated);
        $consent = UpdateInsightsConsentAction::run($request, $consentData, $status, $region);

        return response()->json([
            'visit_id' => $consent->visit?->uuid,
            'enabled_categories' => array_map(
                static fn (InsightsConsentCategory $category): string => $category->value,
                $consent->categories->enabledCategories(),
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function consentDataForStatus(InsightsConsentStatus $status, array $validated): InsightsConsentData
    {
        if ($status === InsightsConsentStatus::AcceptedAll) {
            return new InsightsConsentData(
                essential: true,
                insights: true,
                marketing: true,
                preferences: true,
            );
        }

        if ($status === InsightsConsentStatus::RejectedNonEssential) {
            return new InsightsConsentData(essential: true);
        }

        return new InsightsConsentData(
            essential: true,
            insights: (bool) data_get($validated, 'categories.insights', false),
            marketing: (bool) data_get($validated, 'categories.marketing', false),
            preferences: (bool) data_get($validated, 'categories.preferences', false),
        );
    }
}
