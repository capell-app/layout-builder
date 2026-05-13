# Widget Rendering: Blade vs Livewire

## How widget rendering works

Every widget on a page is rendered through `resources/views/components/layout/widget.blade.php`. This component inspects `$widget->getMetaComponentType()` and routes to one of two paths:

```blade
@if ($type === 'blade')
    <x-dynamic-component
        :component="$component"
        :$widget
        ...
    />
@elseif ($type === 'livewire')
    @livewire($component, [...], key(...))
@endif
```

The `$type` value comes from the widget's `meta['component_type']` field. If not set, it defaults to `'blade'`.

## Stable frontend component keys

Asset-backed widgets should store stable frontend component keys instead of package Blade namespaces. For example, store `section.block` or `section.team-member` in widget asset configuration, not `capell-layout-builder::section.block`.

The frontend component registry resolves that stable key to the active Blade implementation at render time. Content Sections registers neutral defaults, and the core layout builder APIs or a theme package can override the same keys with richer templates:

```php
use Capell\Frontend\Contracts\FrontendComponentRegistryInterface;

$this->callAfterResolving(
    FrontendComponentRegistryInterface::class,
    fn (FrontendComponentRegistryInterface $registry): FrontendComponentRegistryInterface => $registry
        ->register(
            key: 'section.block',
            component: 'capell-example-theme::section.block',
            aliases: [
                'capell-content-sections::section.block',
                'capell-layout-builder::section.block',
            ],
            props: [
                'asset',
                'class',
                'color',
                'icon',
                'image',
                'linkText',
                'loop',
                'meta',
                'size',
                'summary',
                'title',
                'url',
            ],
        ),
);
```

This keeps saved content portable. A theme can replace the template without requiring migrations, manual edits to existing widget assets, or duplicate enum values for each package namespace.

## When to use a Blade component (default)

Use a Blade component for any widget that:

- Reads data from model relations (`$widget->translation`, `$widget->image`, `$widget->backgroundImage`, `$widget->assets`)
- Reads configuration from `$widget->getMeta('key')`
- Has no server-side interactivity (no form submissions, no real-time updates)

This is the correct approach for the vast majority of widgets. **Blade is the default.**

## When to use a Livewire component

Only use Livewire when the widget needs:

- Reactive state (user input that changes the rendered output without a full page reload)
- Server-side form submissions within the widget (e.g. contact form-builder that validate and send emails)
- Real-time data polling

## How to create a Blade widget

### 1. Create the Blade view

Place the view in `resources/views/components/widget/` or `resources/views/components/modern/`:

```blade
<?php declare(strict_types=1); ?>

@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'someOption' => $widget->getMeta('some_option', 'default'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-my-widget"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section>
        @if ($title)
            <h2>{{ $title }}</h2>
        @endif

        {{-- ... --}}
    </section>
</x-capell-layout-builder::widget.wrapper>
```

The `class` attribute on `widget.wrapper` is the CSS selector used in tests (`widget-my-widget`).

### 2. Register a component enum value

Add a case to `WidgetComponentEnum`:

```php
case MyWidget = 'capell-layout-builder::modern.my-widget'; // for components/modern/my-widget.blade.php
case MyWidget = 'capell-layout-builder::widget.my-widget'; // for components/widget/my-widget.blade.php
```

The string value is the Blade component name — it maps directly to the file path under `resources/views/components/`.

### 3. Add a WidgetCreator method

```php
public function myWidget(?Type $type = null): Widget
{
    $type ??= resolve(TypeCreator::class)->defaultWidgetType();

    return $this->widgetModel::query()->firstOrCreate(['key' => 'my-widget'], [
        'name' => 'My Widget',
        'type_id' => $type->id,
        'meta' => [
            'component' => WidgetComponentEnum::MyWidget,
            'some_option' => 'value',
            'margin' => ['lg'],
        ],
    ]);
}
```

The `meta['component']` value drives the component resolution in `widget.blade.php`.

## Data available in a Blade widget

| Source                     | Example                                       |
| -------------------------- | --------------------------------------------- |
| Translation title          | `$widget->translation?->title`                |
| Translation content (HTML) | `$widget->translation?->content`              |
| Meta config                | `$widget->getMeta('columns', 3)`              |
| Primary image              | `$widget->image` (Media model)                |
| Background image           | `$widget->backgroundImage` (Media model)      |
| All images                 | `$widget->assets` (Collection of WidgetAsset) |

Images are `Spatie\MediaLibrary` models. Use `$image->getFullUrl()` for the URL and `$image->name` for the alt text.

## Testing Blade widgets

Use `TestingFrontend` + DOM assertions. The widget must be in a layout on a page to be rendered:

```php
uses(TestingFrontend::class);

it('renders my widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->myWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $image = Media::factory()->model($widget)->image()->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-my-widget',
            fn (AssertElement $elm) => $elm
                ->containsText($translation->title)
                ->find('img', fn (AssertElement $img) => $img
                    ->has('alt', $image->name)
                    ->has('src', $image->getFullUrl())
                )
        );
});
```

Place package-level tests beside the package that owns the widget, and place core layout builder coverage in the admin/frontend core package tests.
