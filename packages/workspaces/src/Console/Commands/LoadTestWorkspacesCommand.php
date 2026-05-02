<?php

declare(strict_types=1);

namespace Capell\Workspaces\Console\Commands;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceContext;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates synthetic workspaces, populates them with fixture rows, and
 * optionally publishes a subset — reporting wall-clock timings per phase
 * so scale regressions are visible. Intended for throwaway / local
 * databases; refuses to run on production unless --force is passed.
 */
class LoadTestWorkspacesCommand extends Command
{
    /** @var string */
    protected $signature = 'capell:workspaces:load-test
        {--workspaces=10 : Number of workspaces to create}
        {--rows-per-workspace=100 : Fixture rows per workspace}
        {--fresh : Truncate the fixture workspace tables first}
        {--publish= : Publish the first N workspaces after populating (defaults to 0)}
        {--force : Allow running outside local/testing environments}';

    /** @var string */
    protected $description = 'Generate synthetic workspaces to benchmark the editorial pipeline.';

    public function handle(): int
    {
        $environment = app()->environment();

        if (! in_array($environment, ['local', 'testing'], true) && ! $this->option('force')) {
            $this->error(sprintf(
                'Refusing to run load test in "%s" environment. Pass --force to override.',
                $environment,
            ));

            return self::FAILURE;
        }

        $workspaceCount = (int) $this->option('workspaces');
        $rowsPerWorkspace = (int) $this->option('rows-per-workspace');
        $publishCount = (int) ($this->option('publish') ?? 0);

        $modelClass = $this->resolveFixtureModelClass();
        $this->ensureFixtureTable($modelClass);

        if (! WorkspaceRegistry::isRegistered($modelClass)) {
            WorkspaceRegistry::register($modelClass);
        }

        if ($this->option('fresh')) {
            $this->truncateFixtures($modelClass);
        }

        $creationStartedAt = microtime(true);
        $workspaces = [];
        for ($workspaceIndex = 0; $workspaceIndex < $workspaceCount; $workspaceIndex++) {
            $workspaces[] = Workspace::factory()->open()->create([
                'name' => 'Load-test workspace ' . ($workspaceIndex + 1),
            ]);
        }

        $creationSeconds = microtime(true) - $creationStartedAt;

        $editStartedAt = microtime(true);
        foreach ($workspaces as $workspace) {
            WorkspaceContext::set($workspace);
            try {
                for ($rowIndex = 0; $rowIndex < $rowsPerWorkspace; $rowIndex++) {
                    $modelClass::query()
                        ->withoutGlobalScopes()
                        ->create([
                            'workspace_id' => $workspace->id,
                            'uuid' => (string) Str::uuid(),
                            'name' => 'row-' . $rowIndex,
                        ]);
                }
            } finally {
                WorkspaceContext::set(null);
            }
        }

        $editSeconds = microtime(true) - $editStartedAt;

        $publishSeconds = 0.0;
        if ($publishCount > 0) {
            $publisher = new Publisher;
            $publishStartedAt = microtime(true);

            foreach (array_slice($workspaces, 0, $publishCount) as $workspace) {
                $workspace->status = WorkspaceStatusEnum::Approved;
                $workspace->approved_at = now();
                $workspace->save();

                $publisher->publish($workspace, bypassWindow: true);
            }

            $publishSeconds = microtime(true) - $publishStartedAt;
        }

        $this->components->info(sprintf(
            'Created %d workspaces in %.3fs (%.1f ws/s)',
            $workspaceCount,
            $creationSeconds,
            $creationSeconds > 0 ? $workspaceCount / $creationSeconds : 0.0,
        ));
        $this->components->info(sprintf(
            'Seeded %d rows in %.3fs (%.1f rows/s)',
            $workspaceCount * $rowsPerWorkspace,
            $editSeconds,
            $editSeconds > 0 ? ($workspaceCount * $rowsPerWorkspace) / $editSeconds : 0.0,
        ));

        if ($publishCount > 0) {
            $this->components->info(sprintf(
                'Published %d workspaces in %.3fs',
                $publishCount,
                $publishSeconds,
            ));
        }

        return self::SUCCESS;
    }

    /** @return class-string<Model> */
    private function resolveFixtureModelClass(): string
    {
        $candidates = [
            WorkspaceDraftableFixture::class,
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'No workspace fixture model is available. Load-test requires the test-suite fixture to be on the autoloader.',
        );
    }

    /** @param  class-string<Model>  $modelClass */
    private function ensureFixtureTable(string $modelClass): void
    {
        $table = (new $modelClass)->getTable();

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint) use ($table): void {
            unset($table);
            $blueprint->id();
            $blueprint->unsignedBigInteger('workspace_id')->default(0)->index();
            $blueprint->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
            $blueprint->uuid('uuid');
            $blueprint->string('name');
            $blueprint->timestamps();
        });
    }

    /** @param  class-string<Model>  $modelClass */
    private function truncateFixtures(string $modelClass): void
    {
        $table = (new $modelClass)->getTable();
        DB::table($table)->truncate();
    }
}
