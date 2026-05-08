<?php

declare(strict_types=1);

namespace Capell\AccessGate\Console\Commands;

use Capell\AccessGate\Actions\SetupDefaultAccessAreaAction;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Console\Command;

final class AccessGateInstallCommand extends Command
{
    protected $signature = 'capell:access-gate-install';

    protected $description = 'Install Access Gate publishables, run migrations, and create the paused default area.';

    public function handle(SetupDefaultAccessAreaAction $setupDefaultArea): int
    {
        $schemaIsReady = $this->accessGateSchemaIsReady();

        foreach ($this->publishTags($schemaIsReady) as $tag) {
            $this->callSilent('vendor:publish', [
                '--tag' => $tag,
                '--force' => false,
            ]);
        }

        $this->info(__('capell-access-gate::install.published'));

        $exitCode = $schemaIsReady
            ? self::SUCCESS
            : $this->call('migrate', [
                '--force' => true,
            ]);

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $area = $setupDefaultArea->handle();

        $this->info(__('capell-access-gate::install.default_area_ready', ['key' => $area->key]));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function publishTags(bool $schemaIsReady): array
    {
        $tags = [
            'capell-access-gate-config',
            'capell-access-gate-views',
            'capell-access-gate-translations',
        ];

        if (! $schemaIsReady) {
            $tags[] = 'capell-access-gate-migrations';
        }

        return $tags;
    }

    private function accessGateSchemaIsReady(): bool
    {
        $schema = AccessGateSchema::builder();
        $areasTable = (new Area)->getTable();

        return $schema->hasTable($areasTable)
            && $schema->hasColumn($areasTable, 'site_id')
            && $schema->hasTable('access_gate_registrations')
            && $schema->hasTable('access_gate_grants')
            && $schema->hasTable('access_gate_claim_tokens')
            && $schema->hasTable('access_gate_browser_tokens')
            && $schema->hasTable('access_gate_events');
    }
}
