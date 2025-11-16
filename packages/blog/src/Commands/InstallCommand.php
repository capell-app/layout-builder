<?php

declare(strict_types=1);

namespace Capell\Blog\Commands;

use Capell\Blog\Actions\InstallBlogPackageAction;
use Illuminate\Console\Command;

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
