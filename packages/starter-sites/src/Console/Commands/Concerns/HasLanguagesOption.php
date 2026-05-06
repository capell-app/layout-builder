<?php

declare(strict_types=1);

namespace Capell\StarterSites\Console\Commands\Concerns;

use Capell\Core\Console\Commands\Concerns\PromptsWithOptionFallback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\multiselect;

/**
 * @mixin Command
 */
trait HasLanguagesOption
{
    use PromptsWithOptionFallback;

    /**
     * @return array<int, string>|null
     */
    private function getDemoLanguages(): ?array
    {
        $languageOption = $this->option('languages');
        if (is_string($languageOption) && $languageOption !== '') {
            return array_values(array_filter(
                array_map(trim(...), explode(',', $languageOption)),
                static fn (string $language): bool => $language !== '',
            ));
        }

        $demoLanguages = collect(config('capell-starter-sites.languages', []))
            ->mapWithKeys(fn (array $language, string $key): array => [$key => $language['name']])
            ->all();

        $databaseLanguages = Schema::hasTable('languages')
            ? DB::table('languages')
                ->whereIn('code', array_keys($demoLanguages))
                ->pluck('name', 'code')
                ->toArray()
            : [];

        $this->requireInteractiveOrFail('Example site languages', 'Pass --languages=<comma,separated,codes>.');

        $selectedLanguages = multiselect(
            label: 'Choose the example site languages?',
            options: $demoLanguages,
            default: count($databaseLanguages) > 0 ? array_keys($databaseLanguages) : [array_key_first($demoLanguages)],
            required: true,
        );

        return is_array($selectedLanguages)
            ? $selectedLanguages
            : (is_string($selectedLanguages) ? explode(',', $selectedLanguages) : null);
    }
}
