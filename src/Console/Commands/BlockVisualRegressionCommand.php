<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\BuildBlockVisualRegressionManifestAction;
use Illuminate\Console\Command;

final class BlockVisualRegressionCommand extends Command
{
    protected $signature = 'capell:layout-builder-block-visual-regression
        {mode : capture or assert}
        {--block= : Limit to one block key}
        {--theme= : Limit to one theme key}
        {--variant= : Limit to one variant key}
        {--changed : Reserved for changed-block runners}
        {--concurrency=2 : Browser capture concurrency for runner integrations}
        {--ci-limit= : Maximum entries to emit in CI}';

    protected $description = 'Build deterministic block visual regression capture/assert entries.';

    public function handle(): int
    {
        $mode = (string) $this->argument('mode');
        if (! in_array($mode, ['capture', 'assert'], true)) {
            $this->error('Mode must be capture or assert.');

            return self::FAILURE;
        }

        $limit = $this->option('ci-limit');
        $entries = BuildBlockVisualRegressionManifestAction::run(
            block: is_string($this->option('block')) ? $this->option('block') : null,
            variant: is_string($this->option('variant')) ? $this->option('variant') : null,
            theme: is_string($this->option('theme')) ? $this->option('theme') : null,
            limit: is_numeric($limit) ? (int) $limit : null,
        );

        $this->line(json_encode([
            'mode' => $mode,
            'changedOnly' => (bool) $this->option('changed'),
            'concurrency' => max(1, (int) $this->option('concurrency')),
            'entries' => $entries,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
