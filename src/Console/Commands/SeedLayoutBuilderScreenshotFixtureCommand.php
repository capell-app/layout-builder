<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\SeedLayoutBuilderScreenshotFixtureAction;
use Illuminate\Console\Command;
use Throwable;

final class SeedLayoutBuilderScreenshotFixtureCommand extends Command
{
    protected $signature = 'capell:layout-builder-screenshot-fixture {--force : Confirm an intentional disposable screenshot seed}';

    protected $description = 'Seed Layout Builder record state for an explicit disposable screenshot run';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to seed screenshot fixtures without --force.');

            return self::FAILURE;
        }

        try {
            $count = SeedLayoutBuilderScreenshotFixtureAction::run();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Layout Builder screenshot fixture initialized with %d widgets.', $count));

        return self::SUCCESS;
    }
}
