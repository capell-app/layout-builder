# Filament Admin Integration Guide

> How to integrate modern widgets into your Filament admin panel for zero-code widget customization

---

## Overview

The `ModernHeroBannerSchema`, `ModernCardGridSchema`, and `ModernCTASectionSchema` classes provide complete Filament form configurations for customizing widgets without any coding.

**Location:** `src/Filament/Schemas/Widgets/`

---

## Using Schemas in Widget Type Classes

Each widget type in your Capell installation can use these schemas to provide admin customization.

### Example: Hero Banner Widget Type

Create a new widget type schema that extends or composes the modern schema:

```php
// app/Capell/Schemas/WidgetTypes/HeroBannerWidgetSchema.php

namespace App\Capell\Schemas\WidgetTypes;

use Capell\Mosaic\Filament\Schemas\Widgets\ModernHeroBannerSchema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;

class HeroBannerWidgetSchema
{
    public static function getSchema(): array
    {
        return [
            Tabs::make('Widget Configuration')
                ->tabs([
                    Tabs\Tab::make('Content')
                        ->schema(array_filter(
                            ModernHeroBannerSchema::getFormSchema(),
                            fn ($component) => $component->getName() === 'Content'
                        )),

                    Tabs\Tab::make('Styling')
                        ->schema(array_filter(
                            ModernHeroBannerSchema::getFormSchema(),
                            fn ($component) => $component->getName() === 'Styling'
                        )),

                    Tabs\Tab::make('Advanced')
                        ->schema(array_filter(
                            ModernHeroBannerSchema::getFormSchema(),
                            fn ($component) => $component->getName() === 'Advanced'
                        )),
                ])
        ];
    }

    public static function getDefaults(): array
    {
        return ModernHeroBannerSchema::getDefaults();
    }
}
```

### Example: Using in Filament Resource

```php
// app/Filament/Resources/PageResource/Pages/EditPage.php

use Filament\Forms\Components\Repeater;
use App\Capell\Schemas\WidgetTypes\HeroBannerWidgetSchema;

class EditPage extends EditRecord
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('widgets')
                    ->label('Page Widgets')
                    ->schema([
                        Select::make('type')
                            ->label('Widget Type')
                            ->options([
                                'hero' => 'Hero Banner',
                                'cards' => 'Card Grid',
                                'cta' => 'CTA Section',
                            ])
                            ->reactive(),

                        // Hero Banner Schema
                        ...collect(HeroBannerWidgetSchema::getFormSchema())
                            ->when(
                                fn () => $this->data['widgets'][0]['type'] === 'hero',
                                fn ($schema) => $schema->all(),
                                fn () => []
                            )
                            ->toArray(),

                        // Similar for CardGrid and CTA schemas...
                    ])
                    ->addActionLabel('Add Widget'),
            ]);
    }
}
```

---

## Schema Methods

### `getFormSchema(): array`

Returns an array of Filament form components organized in sections:

- **Content Section:** Text, titles, buttons, links
- **Styling Section:** Colors, gradients, variants, layout
- **Advanced Section:** Custom CSS, display options

```php
$schema = ModernHeroBannerSchema::getFormSchema();
// Returns array of Section/Grid/TextInput/etc. components
```

### `getDefaults(): array`

Returns default values for new widgets:

```php
$defaults = ModernHeroBannerSchema::getDefaults();

// Result:
[
    'title' => 'Welcome to Capell',
    'subtitle' => 'Create beautiful layouts without code',
    'primaryCta' => [...],
    'height' => 'lg',
    'accentColor' => 'tertiary',
    // ...
]
```

---

## Complete Integration Example

### Step 1: Create a Widget Trait

```php
// app/Capell/Schemas/Concerns/HasModernWidgets.php

trait HasModernWidgets
{
    public static function getHeroBannerSchema(): array
    {
        return ModernHeroBannerSchema::getFormSchema();
    }

    public static function getCardGridSchema(): array
    {
        return ModernCardGridSchema::getFormSchema();
    }

    public static function getCTASectionSchema(): array
    {
        return ModernCTASectionSchema::getFormSchema();
    }

    public static function getWidgetSchema($type): array
    {
        return match ($type) {
            'hero' => self::getHeroBannerSchema(),
            'cards' => self::getCardGridSchema(),
            'cta' => self::getCTASectionSchema(),
            default => [],
        };
    }
}
```

### Step 2: Use in a Custom Field Class

```php
// app/Capell/Filament/Fields/WidgetEditor.php

use Filament\Forms\Components\Field;
use App\Capell\Schemas\Concerns\HasModernWidgets;

class WidgetEditor extends Field
{
    use HasModernWidgets;

    public static function make(string $name): static
    {
        return parent::make($name)
            ->view('widgets.editor')
            ->getStateUsing(fn ($record) => $record->widgets ?? []);
    }

    public function getFormSchema(string $widgetType): array
    {
        return self::getWidgetSchema($widgetType);
    }
}
```

### Step 3: Use in Your Filament Resource

```php
// app/Filament/Resources/PageResource/Pages/EditPage.php

class EditPage extends EditRecord
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('widgets')
                    ->label('Page Sections')
                    ->schema([
                        Select::make('type')
                            ->label('Section Type')
                            ->options([
                                'hero' => 'Hero Banner',
                                'cards' => 'Card Grid',
                                'cta' => 'CTA Section',
                            ])
                            ->reactive()
                            ->required(),

                        // Dynamically load schema based on type
                        ...WidgetEditor::getWidgetSchema('hero')
                            ->when(
                                fn (Get $get) => $get('type') === 'hero'
                            ),

                        ...WidgetEditor::getWidgetSchema('cards')
                            ->when(
                                fn (Get $get) => $get('type') === 'cards'
                            ),

                        ...WidgetEditor::getWidgetSchema('cta')
                            ->when(
                                fn (Get $get) => $get('type') === 'cta'
                            ),
                    ])
                    ->collapsible()
                    ->addActionLabel('Add Section'),
            ]);
    }
}
```

---

## Customizing Schemas

You can extend the provided schemas to add custom fields:

```php
// app/Capell/Schemas/WidgetTypes/CustomHeroBannerSchema.php

use Capell\Mosaic\Filament\Schemas\Widgets\ModernHeroBannerSchema;
use Filament\Forms\Components\FileUpload;

class CustomHeroBannerSchema extends ModernHeroBannerSchema
{
    public static function getFormSchema(): array
    {
        $schema = parent::getFormSchema();

        // Add custom fields
        return [
            ...$schema,

            Section::make('SEO')
                ->schema([
                    TextInput::make('data.meta_title'),
                    TextArea::make('data.meta_description'),
                ]),

            Section::make('Media')
                ->schema([
                    FileUpload::make('data.backgroundImage')
                        ->disk('public')
                        ->visibility('public'),
                ]),
        ];
    }
}
```

---

## Form Validation

Schemas include basic validation. Add custom validation rules:

```php
TextInput::make('data.title')
    ->required()
    ->maxLength(100)
    ->rules(['string', 'required'])
    ->messages([
        'required' => 'Title is required for hero section',
        'max' => 'Title must not exceed 100 characters',
    ])
```

---

## Preview in Admin

To show a live preview of widgets in Filament:

```php
// In your EditPage form

Tabs::make('Widget Configuration')
    ->tabs([
        Tabs\Tab::make('Editor')
            ->schema([...ModernHeroBannerSchema::getFormSchema()]),

        Tabs\Tab::make('Preview')
            ->view('widgets.preview', ['widget' => $this->record->widgets[0] ?? []])
            ->disabled(fn ($get) => ! $get('widgets.0')),
    ])
```

Create a preview blade view:

```blade
<!-- resources/views/widgets/preview.blade.php -->

<div class="rounded-lg overflow-hidden border border-gray-200">
    @if ($widget['type'] === 'hero')
        <x-mosaic::modern.hero-banner
            :title="$widget['data']['title'] ?? ''"
            :subtitle="$widget['data']['subtitle'] ?? ''"
            :primaryCta="$widget['data']['primaryCta'] ?? []"
            :customizable="false"
        />
    @endif
</div>
```

---

## Database Storage

Store widget data in your page/section model:

```php
// Migration
Schema::create('page_widgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->string('type'); // 'hero', 'cards', 'cta'
    $table->json('data'); // Widget-specific data
    $table->integer('order')->default(0);
    $table->timestamps();
});

// Model
class PageWidget extends Model
{
    protected $casts = [
        'data' => 'array',
    ];

    public function renderComponent()
    {
        return match ($this->type) {
            'hero' => view('components.modern.hero-banner', $this->data),
            'cards' => view('components.modern.card-grid', $this->data),
            'cta' => view('components.modern.cta-section', $this->data),
        };
    }
}
```

---

## Tips & Best Practices

1. **Always call `getDefaults()`** when creating new widgets to ensure all required fields have values
2. **Use `->reactive()`** on Select fields to conditionally show/hide related inputs
3. **Group related fields** using `Group::make()` for better UX
4. **Add helpful text** with `->helperText()` to guide admins
5. **Limit repeater items** with `->minItems()` and `->maxItems()` for cards
6. **Store data as JSON** in your database for flexibility
7. **Validate before rendering** - ensure all required fields exist before displaying

---

## Testing Schemas

```php
// tests/Filament/WidgetSchemasTest.php

class WidgetSchemasTest extends TestCase
{
    public function test_hero_schema_renders(): void
    {
        $schema = ModernHeroBannerSchema::getFormSchema();

        $this->assertNotEmpty($schema);
        $this->assertIsArray($schema);
    }

    public function test_hero_defaults_complete(): void
    {
        $defaults = ModernHeroBannerSchema::getDefaults();

        $this->assertArrayHasKey('title', $defaults);
        $this->assertArrayHasKey('primaryCta', $defaults);
        $this->assertArrayHasKey('height', $defaults);
    }
}
```

---

## Troubleshooting

**Q: Schema fields not showing in admin?**
A: Ensure `->when()` conditions are correct and sections are not filtered out.

**Q: Widget data not saving?**
A: Check database `json` column is properly cast in model: `protected $casts = ['data' => 'array'];`

**Q: Repeater not working for cards?**
A: Verify nested field names match expected structure in blade component props.

**Q: Preview not rendering?**
A: Check component path in view and ensure data is passed correctly: `:title="$widget['data']['title']"`

---

## Next Steps

1. Copy schema classes to your project
2. Create widget type schemas extending these
3. Integrate into your Filament resources
4. Test rendering and data persistence
5. Add preview functionality
6. Create documentation for your admin users

---

**Last Updated:** April 18, 2026
**Status:** Ready for Production ✅
