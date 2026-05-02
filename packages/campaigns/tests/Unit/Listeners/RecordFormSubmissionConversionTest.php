<?php

declare(strict_types=1);

use Capell\Campaigns\Enums\ConversionGoalType;
use Capell\Campaigns\Listeners\RecordFormSubmissionConversion;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignGroup;
use Capell\Campaigns\Models\CampaignLandingPage;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Events\FormSubmitted;
use Capell\Forms\Models\Form;

it('records form conversions with submission source and landing page attribution', function (): void {
    $campaignGroup = CampaignGroup::factory()->create([
        'utm_campaign' => 'spring-launch',
    ]);
    $goal = CampaignConversionGoal::factory()
        ->for($campaignGroup, 'campaignGroup')
        ->create([
            'type' => ConversionGoalType::FormSubmission,
            'target' => 'lead-form',
        ]);
    $page = Page::factory()->create();

    PageUrl::factory()
        ->page($page)
        ->create([
            'site_id' => $page->site_id,
            'url' => '/signup',
        ]);

    $landingPage = CampaignLandingPage::factory()
        ->for($campaignGroup, 'campaignGroup')
        ->create([
            'page_id' => $page->getKey(),
            'primary_goal_id' => $goal->getKey(),
            'utm_content' => 'hero',
            'utm_term' => 'launch',
        ]);
    $form = Form::factory()->create([
        'site_id' => $page->site_id,
        'name' => 'Lead form',
        'handle' => 'lead-form',
    ]);
    $submission = $form->submissions()->create([
        'site_id' => $form->site_id,
        'payload' => ['values' => ['email' => 'ben@example.com']],
        'meta' => new SubmissionMetaData(
            url: 'https://capell.test/signup?utm_campaign=spring-launch&utm_source=newsletter&utm_medium=email',
            referer: 'https://example.test/',
        ),
        'submitted_at' => now(),
    ]);

    resolve(RecordFormSubmissionConversion::class)->handle(new FormSubmitted(
        form: $form,
        submission: $submission,
        metadata: $submission->meta,
    ));
    resolve(RecordFormSubmissionConversion::class)->handle(new FormSubmitted(
        form: $form,
        submission: $submission,
        metadata: $submission->meta,
    ));

    $conversion = CampaignConversion::query()->firstOrFail();

    expect(CampaignConversion::query()->count())->toBe(1)
        ->and($conversion->campaign_conversion_goal_id)->toBe($goal->getKey())
        ->and($conversion->campaign_landing_page_id)->toBe($landingPage->getKey())
        ->and($conversion->source_type)->toBe($submission->getMorphClass())
        ->and($conversion->source_id)->toBe($submission->getKey())
        ->and($conversion->attribution->landingUrl)->toBe('https://capell.test/signup?utm_campaign=spring-launch&utm_source=newsletter&utm_medium=email')
        ->and($conversion->attribution->referrerUrl)->toBe('https://example.test/')
        ->and($conversion->attribution->utmCampaign)->toBe('spring-launch')
        ->and($conversion->attribution->utmSource)->toBe('newsletter')
        ->and($conversion->attribution->utmMedium)->toBe('email')
        ->and($conversion->attribution->utmTerm)->toBe('launch')
        ->and($conversion->attribution->utmContent)->toBe('hero');
});
