<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\BuildWidgetVisualRegressionManifestAction;
use Illuminate\Console\Command;

final class WidgetVisualRegressionCommand extends Command
{
    protected $signature = 'capell:layout-builder-widget-visual-regression
        {mode : capture or assert}
        {--widget= : Limit to one widget key}
        {--theme= : Limit to one theme key}
        {--variant= : Limit to one variant key}
        {--changed : Reserved for changed-widget runners}
        {--concurrency=2 : Browser capture concurrency for runner integrations}
        {--ci-limit= : Maximum entries to emit in CI}';

    protected $description = 'Build deterministic widget visual regression capture/assert entries.';

    public function handle(): int
    {
        $modeArgument = $this->argument('mode');
        $mode = is_string($modeArgument) ? $modeArgument : '';

        if (! in_array($mode, ['capture', 'assert'], true)) {
            $this->error('Mode must be capture or assert.');

            return self::FAILURE;
        }

        $limit = $this->option('ci-limit');
        $entries = BuildWidgetVisualRegressionManifestAction::run(
            widget: is_string($this->option('widget')) ? $this->option('widget') : null,
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
