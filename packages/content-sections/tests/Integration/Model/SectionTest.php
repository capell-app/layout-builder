<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Capell\ContentSections\Tests\ContentSectionsTestCase;
use Illuminate\Database\Eloquent\Relations\Relation;

uses(ContentSectionsTestCase::class);

it('uses the existing sections table and section morph alias', function (): void {
    $section = Section::factory()->create(['name' => 'Feature strip']);

    expect($section->getTable())->toBe('sections')
        ->and($section->getMorphClass())->toBe('section')
        ->and(Relation::getMorphedModel('section'))->toBe(Section::class);
});
