<?php

declare(strict_types=1);

namespace Capell\Address\Support\Language;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class FlagsService
{
    private const CACHE_KEY = 'capell_address.language_flags.available';

    private const CACHE_TTL = 3600;

    private const SVG_PREFIX = '1x1';

    private readonly string $svgDirectory;

    public function __construct()
    {
        $installPath = InstalledVersions::getInstallPath('stijnvanouplines/blade-country-flags');

        $this->svgDirectory = rtrim((string) $installPath, DIRECTORY_SEPARATOR) . '/resources/svg/';
    }

    /**
     * @return array<int, string>
     */
    public function availableFlags(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            if (! File::isDirectory($this->svgDirectory)) {
                return [];
            }

            $flags = [];

            foreach (File::files($this->svgDirectory) as $file) {
                $filename = $file->getFilename();

                if (preg_match('/^' . preg_quote(self::SVG_PREFIX, '/') . '-([^.]+)\\.svg$/', $filename, $matches) === 1) {
                    $flags[] = $matches[1];
                }
            }

            return $flags;
        });
    }

    public function flagExists(string $flagCode): bool
    {
        return in_array($flagCode, $this->availableFlags(), true);
    }
}
