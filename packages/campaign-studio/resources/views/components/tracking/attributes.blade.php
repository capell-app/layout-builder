@props([
    'campaignGroup' => null,
    'conversionGoal' => null,
    'location' => null,
])

@if ($campaignGroup)
        data-campaign-id="{{ $campaignGroup->getKey() }}"
@endif

@if ($conversionGoal)
        data-campaign-goal="{{ $conversionGoal->key }}"
@endif

@if ($location)
        data-campaign-location="{{ $location }}"
@endif
