<?php

declare(strict_types=1);

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('runs demo command and creates articles and tags for the site', function (): void {
    $site = Site::factory()->create();

    CreateBlogPagesAction::shouldRun()->once();

    artisan('capell:blog-create-pages', ['site' => $site->id])
        ->expectsOutput('Blog pages created successfully for site: ' . $site->name)
        ->assertExitCode(Command::SUCCESS);
});
