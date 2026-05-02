<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Support\Language\FlagsService;
use Filament\Forms\Components\Select;

class FlagSelect extends Select
{
    /** @var array<int, string> */
    private const PRIMARY_FLAGS = ['us', 'gb', 'de', 'fr', 'es', 'it'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.flag'))
            ->required()
            ->reactive()
            ->searchable()
            ->prefixIcon(fn (?string $state): string => ($state !== null && $state !== '') ? 'flag-4x3-' . $state : '')
            ->options(fn (): array => $this->getFlagOptions());
    }

    public static function getDefaultName(): ?string
    {
        return 'flag';
    }

    /**
     * @return array<string, string>
     */
    private function getFlagOptions(): array
    {
        $labels = collect(resolve(FlagsService::class)->availableFlags())
            ->mapWithKeys(fn (string $flag): array => [$flag => $this->getFlagLabel($flag)]);

        $primary = collect(self::PRIMARY_FLAGS)
            ->filter(fn (string $flag): bool => $labels->has($flag))
            ->mapWithKeys(fn (string $flag): array => [$flag => $labels[$flag]]);

        $remaining = $labels
            ->except(self::PRIMARY_FLAGS)
            ->sort(fn (string $firstLabel, string $secondLabel): int => strcmp($firstLabel, $secondLabel));

        return $primary->all() + $remaining->all();
    }

    private function getFlagLabel(string $flag): string
    {
        return str($flag)
            ->replace('-', ' ')
            ->upper()
            ->toString();
    }
}
