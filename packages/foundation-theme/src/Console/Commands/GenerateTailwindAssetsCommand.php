<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Console\Command;

class GenerateTailwindAssetsCommand extends Command
{
    protected $signature = 'capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Base absolute path for generated CSS files; theme key is appended per enabled Theme (e.g. frontend-default.css)} {--theme-key= : Only regenerate the CSS file for the Theme with this key}';

    protected $description = 'Generate per-theme Tailwind CSS directive files for Capell frontend.';

    public function handle(TailwindAssetsGenerator $generator): int
    {
        if ($this->option('report')) {
            $registry = $generator->collect();

            $report = $registry->toReport();

            $this->line('Tailwind assets report:');
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $overridePath = $this->option('output-path');
        $baseTargetPath = is_string($overridePath) && $overridePath !== '' ? $overridePath : null;

        $themeKey = $this->option('theme-key');

        if (is_string($themeKey) && $themeKey !== '') {
            return $this->generateForSingleTheme($generator, $themeKey, $baseTargetPath);
        }

        $generatedPaths = $generator->generate($baseTargetPath);

        foreach ($generatedPaths as $generatedPath) {
            $this->info(sprintf('Generated Tailwind assets at %s', $generatedPath));
        }

        return self::SUCCESS;
    }

    private function generateForSingleTheme(TailwindAssetsGenerator $generator, string $themeKey, ?string $baseTargetPath): int
    {
        $theme = Theme::query()->where('key', $themeKey)->enabled()->first();

        if ($theme === null) {
            $this->error(sprintf('No enabled theme found with key "%s".', $themeKey));

            return self::FAILURE;
        }

        $generatedPath = $generator->generateForTheme($theme, $baseTargetPath);

        $this->info(sprintf('Generated Tailwind assets at %s', $generatedPath));

        return self::SUCCESS;
    }
}
