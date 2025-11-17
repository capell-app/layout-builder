<?php

declare(strict_types=1);

namespace Capell\Blog\Commands;

use Capell\Blog\Actions\DemoAction;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class DemoCommand extends Command
{
    use HasSitesOption;

    /**
     * The name and signature of the console command.
     *
     * Sites can be provided as comma-separated list: --sites=site1,site2
     */
    protected $signature = 'capell-blog:demo {--sites=} {--author=} {--limit=}';

    /**
     * The console command description.
     */
    protected $description = 'Setup demo blog pages, tags and sample articles for selected sites.';

    public function handle(): int
    {
        $sitesOption = $this->option('sites');
        if ($sitesOption) {
            $siteOptions = is_string($sitesOption)
                ? explode(',', $sitesOption)
                : (is_array($sitesOption) ? $sitesOption : null);
        } else {
            $siteOptions = $this->getSelectedSites();
        }

        if ($siteOptions === null || $siteOptions === []) {
            $this->error('No sites selected or provided.');

            return self::FAILURE;
        }

        $sites = CapellCore::getModel(ModelEnum::Site)::query()
            ->with(['languages'])
            ->whereIn('name', $siteOptions)
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteOptions));

            return self::FAILURE;
        }

        $authorOption = $this->option('author');
        /** @var Model|null $author */
        $author = $authorOption ? CapellCore::getModel('User')::find($authorOption) : null;

        if (! $author && function_exists('auth') && auth()->check()) {
            $author = auth()->user();
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $sites->each(fn (Site $site) => DemoAction::run($site, $author, $limit));

        $this->info('Blog demo setup completed for selected sites.');

        return self::SUCCESS;
    }
}
