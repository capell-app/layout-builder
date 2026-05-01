<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\CalculateSeoScoreAction;
use Capell\SeoTools\Data\SeoIssueData;
use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;

it('calculates an explainable seo score from issue severity', function (): void {
    $score = CalculateSeoScoreAction::run([
        new SeoIssueData(
            key: SeoCheckKeyEnum::MetaTitle,
            severity: SeoIssueSeverityEnum::Critical,
            message: 'Missing meta title.',
        ),
        new SeoIssueData(
            key: SeoCheckKeyEnum::MetaDescription,
            severity: SeoIssueSeverityEnum::Warning,
            message: 'Meta description is short.',
        ),
        new SeoIssueData(
            key: SeoCheckKeyEnum::InternalLinks,
            severity: SeoIssueSeverityEnum::Notice,
            message: 'Add more internal links.',
        ),
    ]);

    expect($score)->toBe(62);
});

it('never returns a score below zero', function (): void {
    $issues = array_fill(
        0,
        8,
        new SeoIssueData(
            key: SeoCheckKeyEnum::Schema,
            severity: SeoIssueSeverityEnum::Critical,
            message: 'Critical issue.',
        ),
    );

    expect(CalculateSeoScoreAction::run($issues))->toBe(0);
});

it('returns full score when there are no issues', function (): void {
    expect(CalculateSeoScoreAction::run([]))->toBe(100);
});
