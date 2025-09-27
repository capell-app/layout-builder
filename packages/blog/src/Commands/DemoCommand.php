<?php

declare(strict_types=1);

namespace Capell\Blog\Commands;

use Capell\Admin\Services\Creator\DemoCreator;
use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function Laravel\Prompts\multisearch;

class DemoCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts demo blog articles into a selected site.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-blog:demo {--author} {--sites=}';

    private DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteIds = is_string($this->option('sites'))
                ? [$this->option('sites')]
                : $this->option('sites');
        } else {
            $siteIds = multisearch(
                'Select a site to insert demo pages',
                options: fn (string $search) => CapellCore::getModel(ModelEnum::Site)::query()
                    ->when(
                        mb_strlen($search) > 0,
                        fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
                    )
                    ->limit(10)
                    ->select(['id', 'name'])
                    ->get()
                    ->mapWithKeys(fn (Site $site): array => [$site->id => $site->name])
                    ->all(),
                validate: [
                    'required',
                    'array',
                    'min:1',
                ],
            );
        }

        $user = $this->option('author') ? CapellCore::getModel('User')::find($this->option('author')) : null;

        $this->demoCreator = new DemoCreator(author: $user);

        $sites = Site::query()->with('languages')->whereIn('id', $siteIds)->get();

        if ($sites->isEmpty()) {
            throw new Exception('Unable to find any sites');
        }

        foreach ($sites as $site) {
            $this->info(sprintf('Selected site: %s', $site->name));

            CreateBlogPagesAction::run($site);

            if (! $this->createDemoPages($site, $user)) {
                $this->error('Failed to create demo pages for the selected site.');

                return Command::FAILURE;
            }
        }

        $this->info('Demo blog articles have been successfully created.');

        return Command::SUCCESS;
    }

    private function createPage(
        array $data,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        null|bool|Page $parent = null,
        ?string $parentName = '',
        string $type = '',
        ?Model $author = null
    ): void {
        $name = Str::title($data['name'][$defaultLanguage->code]);

        if ($type !== '' && $type !== '0') {
            $name .= ' ' . Str::title($type);
        }

        $full_name = $parentName !== null && $parentName !== '' && $parentName !== '0' ? sprintf('%s » %s', $parentName, $name) : $name;

        $page = $this->demoCreator->createPage($data, $site, $languages, $parent, $type);

        if (! isset($data['children'])) {
            return;
        }

        foreach ($data['children'] as $child) {
            $this->createPage(
                data: $child,
                site: $site,
                languages: $languages,
                defaultLanguage: $defaultLanguage,
                parent: $parent === false ? false : $page,
                parentName: $full_name,
                type: $type,
                author: $author
            );
        }
    }

    private function createDemoPages(Site $site, ?Model $user): bool
    {
        $blogPage = BlogLoader::getBlogPage($site);

        if (! $blogPage instanceof Page) {
            $this->error('Blog page not found. Please create a blog page first.');

            return false;
        }

        $demo = config('capell-demo.pages');

        foreach ($demo as $pageData) {
            $this->comment(sprintf('Creating page: %s', $pageData['name']['en']));

            $this->createPage(
                $pageData,
                $site,
                $site->languages,
                $site->language,
                parent: $blogPage,
                type: BlogResourceEnum::Article->value,
                author: $user
            );
        }

        return true;
    }
}
