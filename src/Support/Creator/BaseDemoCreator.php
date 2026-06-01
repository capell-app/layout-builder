<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use BackedEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Exception;
use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use SplFileInfo;

abstract class BaseDemoCreator
{
    protected const string NavigationPackage = 'capell-app/navigation';

    /**
     * @var class-string<Model&HasMedia>
     */
    protected string $contentModel;

    /**
     * @var class-string<Widget>
     */
    protected string $blockModel;

    /**
     * @var class-string<Blueprint>
     */
    protected string $typeModel;

    /**
     * @var class-string<Page>
     */
    protected string $pageModel;

    /**
     * @param  Collection<array-key, mixed>  $siteTree
     * @return array<array-key, mixed>
     */
    protected function navigationPageItems(Collection $siteTree, Language $language): array
    {
        $items = [];

        foreach ($siteTree as $page) {
            $items[(string) Str::uuid()] = [
                'label' => $this->getPageNavigationLabel($page, $language),
                'type' => 'page',
                'data' => [
                    'pageable_id' => $page->id,
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => $page->relationLoaded('children') ? $this->navigationPageItems($page->children, $language) : [],
            ];
        }

        return $items;
    }

    protected function getPageNavigationLabel(Page $page, Language $language): string
    {
        $navigationCreator = NavigationCreator::class;

        if (CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($navigationCreator)) {
            return $navigationCreator::getPageNavigationLabel($page, $language);
        }

        return $page->translation->title ?? $page->name;
    }

    protected function createPageBlockAsset(Widget $block, Pageable $page, string $container, int $occurrence, Model $asset): WidgetAsset
    {
        $blockAsset = DB::transaction(
            fn (): Model => $block->assets()->createOrFirst([
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
                'container' => $container,
                'occurrence' => $occurrence,
                'asset_type' => $asset->getMorphClass(),
                'asset_id' => $asset->getKey(),
            ]),
            attempts: 5,
        );

        throw_unless($blockAsset instanceof WidgetAsset, RuntimeException::class, 'Layout block asset creation must return a block asset model.');

        return $blockAsset;
    }

    protected function requiredWidgetType(BackedEnum|string $key, BackedEnum|string|null $fallback = null): Blueprint
    {
        $key = $key instanceof BackedEnum ? $key->value : $key;
        $fallback = $fallback instanceof BackedEnum ? $fallback->value : $fallback;

        $query = $this->typeModel::query()->where('type', LayoutTypeEnum::Widget->value);

        $type = (clone $query)->firstWhere('key', $key);

        if (! $type instanceof Blueprint && $fallback !== null) {
            $type = (clone $query)->firstWhere('key', $fallback);
        }

        throw_unless($type instanceof Blueprint, RuntimeException::class, sprintf('Missing layout widget type [%s].', $key));

        return $type;
    }

    /**
     * @return array<string, mixed>
     */
    protected function widgetMeta(Widget $widget): array
    {
        return is_array($widget->meta) ? $widget->meta : [];
    }

    protected function defaultSite(): Site
    {
        $site = Site::getDefault();

        throw_unless($site instanceof Site, RuntimeException::class, 'A default site is required to create demo blocks.');

        return $site;
    }

    /** @return MorphMany<Translation, Model> */
    protected function translationsFor(Model $model): MorphMany
    {
        return $model->morphMany(Translation::class, 'translatable');
    }

    /**
     * @return Collection<array-key, mixed>
     */
    protected function createFeatures(Site $site): Collection
    {
        $features = [
            [
                'icon' => 'heroicon-o-light-bulb',
                'title' => 'Reusable CMS Patterns',
                'content' => '<p>We use Laravel packages, Filament resources, and reusable blocks to keep CMS implementations maintainable.</p>',
            ],
            [
                'icon' => 'heroicon-o-academic-cap',
                'title' => 'Expertise',
                'content' => '<p>Our team of experts brings deep industry knowledge and experience to every project.</p>',
            ],
            [
                'icon' => 'heroicon-o-user-group',
                'title' => 'Client-Centric Approach',
                'content' => "<p>We prioritize our clients' needs and work collaboratively to achieve their goals.</p>",
            ],
            [
                'icon' => 'heroicon-o-chart-bar',
                'title' => 'Operational Checks',
                'content' => '<p>We ship with checks for content, assets, cache, and frontend output so teams can verify each release.</p>',
            ],
            [
                'icon' => 'heroicon-o-sparkles',
                'title' => 'Sustainable Practices',
                'content' => '<p>We are committed to sustainable practices that benefit our clients and the environment.</p>',
            ],
            [
                'icon' => 'heroicon-o-shield-check',
                'title' => 'Lockdown',
                'content' => '<p>Lock down the public frontend during an incident while keeping break-glass admin access and preserving the live static page cache for recovery.</p>',
            ],
            [
                'icon' => 'heroicon-o-globe-alt',
                'title' => 'Global Reach',
                'content' => '<p>Our global presence allows us to serve clients across diverse markets and industries.</p>',
            ],
        ];

        $layout = Layout::query()->default()->first();
        $defaultPageType = Blueprint::query()
            ->where('type', 'page')
            ->default()
            ->first();

        throw_unless($layout instanceof Layout, Exception::class, 'Default layout not found');
        throw_unless($defaultPageType instanceof Blueprint, Exception::class, 'Default page type not found');

        $parentPage = Page::query()->firstOrNew([
            'site_id' => $site->id,
            'layout_id' => $layout->id,
            'blueprint_id' => $defaultPageType->id,
            'name' => 'Features',
        ]);

        $parentPage->save();

        $site->languages->each(function (Language $language) use ($parentPage): void {
            $parentPage->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $parentPage->name,
            ]);
        });

        $contentFeatures = new Collection;

        foreach ($features as $feature) {
            $page = Page::query()->firstOrNew([
                'site_id' => $site->id,
                'name' => $feature['title'],
            ]);

            $page->fill([
                'parent_id' => $parentPage->id,
                'layout_id' => $layout->id,
                'blueprint_id' => $defaultPageType->id,
                'meta' => [
                    'icon' => $feature['icon'],
                ],
            ]);

            $page->save();

            $this->createMedia($page);

            $content = $this->contentModel::query()->updateOrCreate([
                'name' => $feature['title'],
            ], [
                'meta' => [
                    'icon' => $feature['icon'],
                    'pageable_id' => $page->id,
                    'pageable_type' => $page->getMorphClass(),
                ],
            ]);

            $this->createMedia($content);

            $contentFeatures->push($content);

            $site->languages->each(function (Language $language) use ($page, $content, $feature): void {
                $page->translations()->firstOrCreate([
                    'language_id' => $language->id,
                ], [
                    'title' => $feature['title'],
                    'content' => $feature['content'],
                ]);

                $this->translationsFor($content)->firstOrCreate([
                    'language_id' => $language->id,
                ], [
                    'title' => $feature['title'],
                    'content' => $feature['content'],
                ]);
            });
        }

        return $contentFeatures;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    protected function createTestimonials(Collection $languages): Collection
    {
        $testimonialContent = $this->contentModel::query()->firstOrCreate([
            'name' => 'Testimonials',
        ], [
            'meta' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
        ]);

        $this->createMedia($testimonialContent);

        $testimonials = [
            [
                'name' => 'John Doe',
                'position' => 'CEO of Example Corp',
                'content' => 'Capell gave our editors a clearer workflow and gave engineering a smaller surface area to maintain.',
            ],
            [
                'name' => 'Jane Smith',
                'position' => 'CTO of Tech Innovations',
                'content' => 'The team at Capell is incredibly knowledgeable and always goes the extra mile for us.',
            ],
            [
                'name' => 'Jeff Wilson',
                'position' => 'Marketing Director at Creative Agency',
                'content' => 'We have seen significant growth since partnering with Capell. Their expertise is unmatched.',
            ],
        ];

        $testimonialsCollection = new Collection;

        $testimonialType = Blueprint::query()->updateOrCreate([
            'key' => 'testimonial',
            'type' => 'section',
        ], [
            'name' => 'Testimonial',
            'admin' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'configurator' => 'testimonial-section',
            ],
        ]);

        foreach ($testimonials as $testimonial) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $testimonial['name'],
                'parent_id' => $testimonialContent->id,
                'blueprint_id' => $testimonialType->id,
            ], [
                'meta' => [
                    'position' => $testimonial['position'],
                ],
            ]);

            $this->createMedia($content);

            $this->translationsFor($content)->createMany(
                $languages
                    ->reject(fn (Language $language): bool => $content->translations->contains('language_id', $language->id))
                    ->map(fn (Language $language): array => [
                        'language_id' => $language->id,
                        'title' => $testimonial['name'],
                        'content' => sprintf('<p>%s</p>', $testimonial['content']),
                    ])
                    ->all(),
            );

            $testimonialsCollection->push($content);
        }

        return $testimonialsCollection;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    protected function createTeamMembers(Collection $languages): Collection
    {
        $teamMembers = [
            [
                'name' => 'Alice Johnson',
                'position' => 'CEO',
                'bio' => '<p>Alice coordinates product priorities, release scope, and editorial feedback across the demo team.</p>',
            ],
            [
                'name' => 'Charlie Brown',
                'position' => 'CFO',
                'bio' => '<p>Charlie manages our finances with precision, ensuring sustainable growth and stability.</p>',
            ],
            [
                'name' => 'Fiona Green',
                'position' => 'Head of HR',
                'bio' => "<p>Fiona is dedicated to building a strong team culture and supporting our employees' growth.</p>",
            ],
            [
                'name' => 'George White',
                'position' => 'Lead Designer',
                'bio' => '<p>George turns design requirements into reusable section patterns and practical editorial controls.</p>',
            ],
            [
                'name' => 'Hannah Blue',
                'position' => 'Senior Developer',
                'bio' => '<p>Hannah is a coding wizard, turning complex problems into elegant solutions.</p>',
            ],
            [
                'name' => 'Ian Black',
                'position' => 'Project Manager',
                'bio' => '<p>Ian keeps our projects on track, ensuring timely delivery and client satisfaction.</p>',
            ],
            [
                'name' => 'Julia Red',
                'position' => 'Content Strategist',
                'bio' => '<p>Julia crafts compelling content strategies that engage and inform our audience.</p>',
            ],
            [
                'name' => 'Kevin Yellow',
                'position' => 'Data Analyst',
                'bio' => '<p>Kevin reviews usage data and release checks so the demo keeps reflecting real CMS workflows.</p>',
            ],
            [
                'name' => 'Laura Purple',
                'position' => 'Customer Success Manager',
                'bio' => '<p>Laura gathers editor feedback and keeps onboarding notes clear for new project teams.</p>',
            ],
            [
                'name' => 'Mike Orange',
                'position' => 'Sales Director',
                'bio' => '<p>Mike drives our sales strategy, helping us reach new heights in revenue.</p>',
            ],
            [
                'name' => 'Nina Pink',
                'position' => 'UX Researcher',
                'bio' => '<p>Nina conducts research to understand user needs, shaping our products for better usability.</p>',
            ],
            [
                'name' => 'Oscar Gray',
                'position' => 'IT Support Specialist',
                'bio' => '<p>Oscar keeps our systems running smoothly, providing technical support to our team.</p>',
            ],
            [
                'name' => 'Quentin Silver',
                'position' => 'Business Analyst',
                'bio' => '<p>Quentin analyzes market trends, helping us identify new opportunities for growth.</p>',
            ],
            [
                'name' => 'Sam White',
                'position' => 'Quality Assurance Specialist',
                'bio' => '<p>Sam ensures our products meet the highest quality standards before they reach our clients.</p>',
            ],
            [
                'name' => 'Victor Blue',
                'position' => 'Network Administrator',
                'bio' => '<p>Victor manages our network infrastructure, ensuring reliable connectivity for our team.</p>',
            ],
            [
                'name' => 'Zane Purple',
                'position' => 'Research Scientist',
                'bio' => '<p>Zane tests integration ideas and documents the ones that belong in the package roadmap.</p>',
            ],
        ];

        $teamContent = $this->contentModel::query()->firstOrNew([
            'name' => 'Team Members',
        ]);

        $meta = $teamContent->meta ?? [];
        $meta['icon'] = 'heroicon-o-users';
        $teamContent->meta = $meta;

        $teamContent->save();

        $teamMembersCollection = new Collection;

        foreach ($teamMembers as $member) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $member['name'],
                'parent_id' => $teamContent->id,
            ], [
                'meta' => [
                    'position' => $member['position'],
                ],
            ]);

            $this->createMedia($content);

            $this->translationsFor($content)->createMany(
                $languages
                    ->reject(fn (Language $language): bool => $content->translations->contains('language_id', $language->id))
                    ->map(fn (Language $language): array => [
                        'language_id' => $language->id,
                        'title' => $member['name'],
                        'content' => $member['bio'],
                    ])
                    ->all(),
            );

            $teamMembersCollection->push($content);
        }

        return $teamMembersCollection;
    }

    protected function createMedia(Model $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = MediaCollectionEnum::Image): void
    {
        if (! $model instanceof HasMedia) {
            return;
        }

        $collectionName = $this->collectionName($collection);

        // Build an optional filter to match existing media by inferred filename when a name is provided
        $filters = [];
        if (! in_array($name, [null, '', '0'], true)) {
            $base = pathinfo(Str::slug($name), PATHINFO_FILENAME);
            $filters = [
                fn (Media $media): bool => str($media->file_name)->contains($base),
            ];
        }

        if ($model->getMedia($collectionName, $filters)->isNotEmpty()) {
            return;
        }

        if ($model instanceof Widget) {
            $this->createBlockMedia($model, $name, $type, $collection);
        }
    }

    protected function createBlockMedia(Widget $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = MediaCollectionEnum::Image): Media
    {
        // Normalize input name and derive extension if provided
        $inputName = in_array($name, [null, '', '0'], true) ? null : $name;
        $inputExt = $inputName !== null ? pathinfo($inputName, PATHINFO_EXTENSION) : '';

        // Decide base demo path and defaults per type
        $isVideo = $type === 'video';
        $demoPath = $this->getDemoResourcePath($isVideo ? 'video' : 'img');

        // Determine filename (without extension) and extension
        $filenameBase = $inputName !== null
            ? pathinfo($inputName, PATHINFO_FILENAME)
            : ($isVideo ? 'SampleVideo_1280x720_1mb' : null);

        $ext = $inputExt !== ''
            ? strtolower($inputExt)
            : ($isVideo ? 'mp4' : 'jpg');

        // Use video collection explicitly
        if ($isVideo) {
            $collection = MediaCollectionEnum::Video;
        }

        // Build the candidate file path
        $demoFile = $filenameBase !== null ? sprintf('%s/%s.%s', $demoPath, $filenameBase, $ext) : '';

        // Fallback handling: if no filename or file missing, choose a random demo image for images
        if ($filenameBase === null || $demoFile === '' || ! file_exists($demoFile)) {
            if ($isVideo) {
                // For videos, keep original demo path and defaults; we'll still attach a poster image below
                // Attempt video default file first
                $filenameBase = 'SampleVideo_1280x720_1mb';
                $ext = $inputExt !== '' ? strtolower($inputExt) : 'mp4';
            } else {
                // For images: pick a random demo image and set explicit jpg (demo images are jpg)
                $demoPath = $this->getDemoResourcePath('img');
                $filenameBase = $this->getRandomDemoImage($demoPath, 'jpg');
                $ext = 'jpg';
            }

            $demoFile = sprintf('%s/%s.%s', $demoPath, $filenameBase, $ext);
        }

        // Create content and link via WidgetAsset
        $content = $this->contentModel::query()->create([
            'name' => str($filenameBase)->title(),
        ]);

        throw_unless($content instanceof HasMedia, RuntimeException::class, 'Demo media content must implement media collections.');

        $model->assets()->create([
            'asset_id' => $content->getKey(),
            'asset_type' => resolve($this->contentModel)->getMorphClass(),
        ]);

        // Attach primary media
        $image = null;
        if (! $isVideo) {
            $image = Image::load($demoFile);
        }

        $media = $content->addMedia($demoFile)
            ->preservingOriginal()
            ->withCustomProperties([
                ...($image instanceof Image ? ['width' => $image->getWidth(), 'height' => $image->getHeight()] : []),
            ])
            ->toMediaCollection($this->collectionName($collection));

        // For videos, also attach a jpg poster image
        if (! $isVideo) {
            return $this->ensureCapellMedia($media);
        }

        $posterPath = $this->getDemoResourcePath('img');
        $posterBase = $this->getRandomDemoImage($posterPath);
        $posterFile = sprintf('%s/%s.jpg', $posterPath, $posterBase);

        $posterImage = Image::load($posterFile);

        $posterMedia = $content->addMedia($posterFile)
            ->preservingOriginal()
            ->withCustomProperties([
                'width' => $posterImage->getWidth(),
                'height' => $posterImage->getHeight(),
            ])
            ->toMediaCollection(MediaCollectionEnum::Image->value);

        return $this->ensureCapellMedia($posterMedia);
    }

    protected function ensureCapellMedia(SpatieMedia $media): Media
    {
        throw_unless($media instanceof Media, RuntimeException::class, 'Demo media creation must return a Capell media model.');

        return $media;
    }

    protected function getRandomDemoImage(string $demo_path, string $extension = 'jpg'): string
    {
        $ext = strtolower($extension);
        $filenames = [];

        foreach (new FilesystemIterator($demo_path, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo) {
                continue;
            }

            if (! $fileInfo->isFile()) {
                continue;
            }

            if (strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION)) !== $ext) {
                continue;
            }

            $filenames[] = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
        }

        throw_if($filenames === [], RuntimeException::class, 'No demo layout builder media files found for .' . $extension . ' in: ' . $demo_path);

        return $filenames[random_int(0, count($filenames) - 1)];
    }

    protected function getDemoResourcePath(string $type): string
    {
        $configuredPath = config('capell-layout-builder.resources.demo_path');

        throw_if(! is_string($configuredPath) || $configuredPath === '', RuntimeException::class, 'Configure capell-layout-builder.resources.demo_path before creating demo layout builder media.');

        $path = rtrim($configuredPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($type, DIRECTORY_SEPARATOR);

        throw_unless(is_dir($path), RuntimeException::class, 'Configured demo layout builder media path does not exist: ' . $path);

        return $path;
    }

    private function collectionName(BackedEnum|string $collection): string
    {
        return is_string($collection) ? $collection : (string) $collection->value;
    }
}
