<?php

declare(strict_types=1);

namespace Capell\DemoKit\Support\Creator;

use BackedEnum;
use Capell\Core\Actions\DummyContentGeneratorAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCreatable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\PageCreator;
use Exception;
use FilesystemIterator;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use SplFileInfo;
use Throwable;
use ZipArchive;

class DemoCreator
{
    use Macroable;

    /** @var class-string<Language> */
    public string $languageModel;

    /** @var class-string<Site> */
    public string $siteModel;

    /** @var class-string<Page> */
    public string $pageModel;

    /** @var class-string<Models\Translation> */
    public string $translationModel;

    /** @var class-string<Layout> */
    public string $layoutModel;

    /** @var class-string<Type> */
    public string $typeModel;

    public function __construct(
        protected ?string $url = null,
        protected ?Model $author = null,
    ) {
        if (in_array($this->url, [null, '', '0'], true)) {
            $this->url = config('app.url');
        }

        $this->languageModel = Language::class;
        $this->layoutModel = Layout::class;
        $this->pageModel = Page::class;
        $this->siteModel = Site::class;
        $this->typeModel = Type::class;
    }

    public static function getDemoResourcePath(?string $folder): string
    {
        return resolve(DemoResourceResolver::class)->resolve($folder);
    }

    /**
     * @param  null|Collection<int, Language>  $languages  = null
     */
    public function setupSite(Site $site, ?Collection $languages = null): void
    {
        $languages ??= $site->languages;
        $title = ctype_digit($site->name[0]) ? $site->name : Str::title($site->name);

        $meta = $site->meta;

        $meta['business_name'] = $title . ' ltd';
        $meta['email'] = config('mail.from.address');
        $meta['phone'] = '0123456789';
        $meta['footer_content'] = 'Footer content here';
        $meta['social_links'] = [
            [
                'type' => 'facebook',
                'url' => 'https://facebook.com',
                'icon' => 'fab-square-facebook',
            ],
            [
                'type' => 'twitter',
                'url' => 'https://twitter.com',
                'icon' => 'fab-square-x-twitter',
            ],
            [
                'type' => 'instagram',
                'url' => 'https://instagram.com',
                'icon' => 'fab-square-instagram',
            ],
        ];

        $site->update(['meta' => $meta]);

        foreach ($languages as $language) {
            $site->translations()->updateOrCreate(['language_id' => $language->id], [
                'title' => $title,
                'meta' => [
                    'description' => 'Description for ' . $title,
                    'footer_copy' => sprintf('<p>&copy; :year %s</p>', $title),
                ],
            ]);

            $url_parts = parse_url((string) $this->url);

            $path = '';
            if (! $language->default) {
                $path .= '/' . $language->code;
            }

            if (! $site->default) {
                $path .= '/' . Str::slug($site->name);
            }

            $site->siteDomains()->firstOrCreate([
                'domain' => $url_parts['host'],
                'path' => $path !== '' && $path !== '0' ? $path : null,
            ], [
                'language_id' => $language->id,
                'default' => $site->siteDomains()->doesntExist(),
            ]);
        }
    }

    public function createDefaultLanguages(?array $languages = null): void
    {
        foreach (config('capell-demo-kit.languages') as $item) {
            if (is_array($languages) && ! in_array($item['code'], $languages, true)) {
                continue;
            }

            $language = $this->languageModel::query()->where('code', $item['code'])->first();

            if ($language !== null) {
                $language->update([
                    'name' => $item['name'],
                    'locale' => $item['locale'],
                    'flag' => $item['flag'],
                    'meta' => [
                        'color' => $item['color'],
                    ],
                ]);

                continue;
            }

            $this->languageModel::query()->create([
                'name' => $item['name'],
                'code' => $item['code'],
                'locale' => $item['locale'],
                'flag' => $item['flag'],
                'default' => $this->languageModel::query()->count() === 0,
                'meta' => [
                    'color' => $item['color'],
                ],
            ]);
        }
    }

    /**
     * @param  null|Collection<int, Language>  $languages  =  null
     */
    public function createPage(
        array $data,
        Site $site,
        ?Collection $languages = null,
        ?Page $parent = null,
        ?Type $type = null,
        ?Layout $layout = null,
        bool $createMedia = true,
        ?PageCreatable $pageCreator = null,
    ): Pageable {
        $languages ??= $site->languages;
        $pageCreator ??= new PageCreator;

        $name = Str::title($data['name']['en']);

        $pageData = [
            'name' => $name,
            'user_id' => $this->author?->getKey(),
            'type_id' => $type?->getKey(),
            'layout_id' => $layout?->getKey(),
            'translations' => [],
            'visible_from' => now()->subDays(random_int(0, 90))->format('Y-m-d'),
        ];

        if ($parent instanceof Pageable) {
            $pageData['parent_id'] = $parent->getKey();
        }

        $languages->each(function (Language $language) use (&$pageData, $name, $data): void {
            $title = Str::title($data['name'][$language->code]);

            $slug = Str::slug($title);

            $desc_content = DummyContentGeneratorAction::run($language->code);

            $pageData['translations'][$language->code] = [
                'title' => $title,
                'content' => $desc_content,
                'meta' => [
                    'description' => str($desc_content)->stripTags()->limit(160),
                    'keywords' => implode(',', array_slice(explode(' ', $title), 0, 10)),
                    'label' => Str::title($data['name'][$language->code] ?? $name),
                    'link_text' => Arr::random([
                        'Learn More',
                        'Read More',
                        'Get Started',
                        'More information',
                        'Unlock the Full Story',
                    ]),
                    'slug' => $slug,
                ],
            ];
        });

        $page = $pageCreator->createPage($pageData, $site, $languages);

        if ($createMedia) {
            $this->createMedia($page, $name);
        }

        return $page;
    }

    public function getRandomDemoImage(string $path, string $extension = 'jpg'): string
    {
        $ext = strtolower($extension);
        $selectedFilename = null;
        $count = 0;

        // Use FilesystemIterator to stream directory entries without loading all into memory.
        $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo) {
                continue;
            }

            if (! $fileInfo->isFile()) {
                continue;
            }

            $fileExtension = strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
            if ($fileExtension !== $ext) {
                continue;
            }

            // Reservoir sampling: replace the selected file with decreasing probability.
            $count++;
            if (random_int(1, $count) === 1) {
                $selectedFilename = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
            }
        }

        throw_if($selectedFilename === null, Exception::class, 'No demo files with extension .' . $extension . ' found in the specified path: ' . $path);

        return $selectedFilename;
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     * @throws Exception
     */
    public function createMedia(Model&HasMedia $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = MediaCollectionEnum::Image): void
    {
        if ($model->getMedia($collection instanceof BackedEnum ? $collection->value : $collection)->isNotEmpty()) {
            return;
        }

        if ($type === 'video') {
            $ext = 'mp4';
            $demo_path = static::getDemoResourcePath('video');
            $filename = $name ?? 'SampleVideo_1280x720_1mb';
            $collection = MediaCollectionEnum::Video;
        } else {
            $ext = 'jpg';
            $demo_path = static::getDemoResourcePath('img');
            $filename = in_array($name, [null, '', '0'], true) ? null : Str::slug($name);
        }

        if ($filename !== null) {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
        }

        $demo_file = sprintf('%s/%s.%s', $demo_path, $filename, $ext);

        if (in_array($filename, ['', '0', [], null], true) || ! File::exists($demo_file)) {
            $demo_path = static::getDemoResourcePath('img');
            $ext = 'jpg';
            $filename = $this->getRandomDemoImage($demo_path, $ext);
            $demo_file = sprintf('%s/%s.%s', $demo_path, $filename, $ext);
        }

        $image = null;
        if ($type !== 'video') {
            try {
                $image = Image::load($demo_file);
            } catch (Throwable) {
                $image = null;
            }
        }

        $customProps = [
            ...(
                $image instanceof Image
                ? ['width' => $image->getWidth(), 'height' => $image->getHeight()]
                : []
            ),
        ];

        if (! File::exists($demo_file)) {
            return;
        }

        $model->addMedia($demo_file)
            ->preservingOriginal()
            ->withCustomProperties($customProps)
            ->toMediaCollection($collection instanceof BackedEnum ? $collection->value : $collection);
    }

    public function setupRelatedSites(): void
    {
        $sites = $this->siteModel::with(['language', 'translations'])->get();
        $defaultSite = $this->siteModel::getDefault();

        $this->attachRelatedSites($defaultSite, $sites);

        $sites->each(function (Site $site): void {
            $relatedSites = $this->findRelatedSites($site);

            $site->related()->attach($relatedSites)->save();
        });
    }

    /**
     * @param  Collection<int, Site>  $sites
     */
    protected function attachRelatedSites(Site $defaultSite, Collection $sites): void
    {
        $defaultSite->related()
            ->attach($sites->where('id', '!=', $defaultSite->id))
            ->save();
    }

    protected function findRelatedSites(Site $site): Collection
    {
        $language_ids = $site->translations->pluck('language_id');

        return $this->siteModel::query()
            ->with(['language'])
            ->withWhereHas(
                'translation',
                fn (BuilderContract $query): BuilderContract => $query->whereIn('translations.language_id', $language_ids),
            )
            ->whereNot('sites.id', $site->id)
            ->get();
    }

    private static function ensureStorageDemoResources(): string
    {
        return resolve(DemoResourceResolver::class)->ensureStorageDemoResources();
    }

    private static function assertSafeDemoZipEntries(ZipArchive $zip): void
    {
        resolve(DemoResourceResolver::class)->assertSafeDemoZipEntries($zip);
    }
}
