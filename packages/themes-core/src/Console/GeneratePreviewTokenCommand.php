<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Console;

use Capell\Themes\Core\Preview\PreviewMode;
use Illuminate\Console\Command;

class GeneratePreviewTokenCommand extends Command
{
    protected $signature = 'themes:preview-token
        {--path= : The path to sign (e.g. /page/my-draft)}
        {--minutes=60 : Token expiry in minutes}
        {--base-url= : Optional base URL to output the full signed URL}';

    protected $description = 'Generate a signed preview token for a given path';

    public function handle(): int
    {
        $path = $this->option('path');

        if (! $path) {
            $this->error('The --path option is required.');

            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');
        $secretKey = config('app.key', 'preview-secret');
        $preview = new PreviewMode(secretKey: $secretKey);
        $token = $preview->generateToken($path, expiresInMinutes: $minutes);

        $this->line('Token: ' . $token);

        $baseUrl = $this->option('base-url');

        if ($baseUrl) {
            $signedUrl = $preview->signedUrl((string) $path, baseUrl: (string) $baseUrl, expiresInMinutes: $minutes);
            $this->line('URL: ' . $signedUrl);
        }

        return self::SUCCESS;
    }
}
