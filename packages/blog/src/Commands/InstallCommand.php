<?php

declare(strict_types=1);

namespace Capell\Blog\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Blog\Actions\InstallBlogPackageAction;
use Capell\Blog\BlogModelRegistrar;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install blog package';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-blog:install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Capell Blog Package...');

        BlogModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        InstallBlogPackageAction::run();

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => [
                    'alter_tags_table',
                ],
                '--path' => __DIR__ . '/../database/migrations',
            ],
        );

        $this->info('Publishing Capell Blog...');
        $this->call('vendor:publish', ['--tag' => 'capell-blog-config']);

        $this->call('migrate');

        $this->info('Capell Blog installation complete.');

        return self::SUCCESS;
    }
}
