<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

class SectionRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $sections = [];

    /**
     * @param  array{label: string, description: string, good_for: list<string>, not_for: list<string>, fields: list<string>, media: list<string>, supports_translations: bool, repeatable: bool}  $descriptor
     */
    public function register(string $key, array $descriptor): void
    {
        $this->sections[$key] = $descriptor;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->sections;
    }

    public function forAi(): string
    {
        if ($this->sections === []) {
            return 'No section types registered.';
        }

        $lines = ['Available section types:'];

        foreach ($this->sections as $key => $descriptor) {
            $goodFor = implode(', ', $descriptor['good_for'] ?? []);
            $notFor = implode(', ', $descriptor['not_for'] ?? []);
            $fields = implode(', ', $descriptor['fields'] ?? []);
            $media = implode(', ', $descriptor['media'] ?? []);

            $lines[] = sprintf(
                '- %s (%s): %s. Good for: %s.%s Fields: %s.%s%s',
                $key,
                $descriptor['label'] ?? $key,
                $descriptor['description'] ?? '',
                $goodFor !== '' ? $goodFor : 'general use',
                $notFor !== '' && $notFor !== '0' ? sprintf(' Avoid for: %s.', $notFor) : '',
                $fields !== '' ? $fields : 'none',
                $media !== '' && $media !== '0' ? sprintf(' Media: %s.', $media) : '',
                ((bool) ($descriptor['repeatable'] ?? false)) ? ' Repeatable.' : '',
            );
        }

        return implode("\n", $lines);
    }
}
