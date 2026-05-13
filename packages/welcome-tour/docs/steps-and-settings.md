# Steps And Settings

Welcome Tour replaces the default admin dashboard with `WelcomeTourDashboard`, registers the Filament tour plugin, and reads tour steps from settings or package config.

## Runtime Surface

| Surface              | Code                            |
| -------------------- | ------------------------------- |
| Enabled flag         | `capell-welcome-tour.enabled`   |
| Default steps        | `capell-welcome-tour.steps`     |
| Settings group       | `welcome-tour`                  |
| Admin panel extender | `WelcomeTourPanelExtender`      |
| User resource bridge | `WelcomeTourUserResourceBridge` |
| Dashboard page       | `WelcomeTourDashboard`          |

## Add a Step in Config

```php
// config/capell-welcome-tour.php
'steps' => [
    [
        'key' => 'host-app.first-page',
        'title' => 'host-app::welcome_tour.first_page_title',
        'description' => 'host-app::welcome_tour.first_page_description',
        'element' => '#first-page-button',
        'icon' => 'heroicon-o-document-plus',
        'icon_color' => 'primary',
        'sort' => 50,
        'visible' => true,
    ],
],
```

`WelcomeTourStepRegistrar` translates `title` and `description`, escapes the description, and skips rows without a key.

## Register a Step From Code

Use `CapellAdmin::registerWelcomeTourStep()` when the step belongs to another package and should only exist when that package is installed.

```php
use Capell\Admin\Facades\CapellAdmin;

CapellAdmin::registerWelcomeTourStep(
    key: 'demo-kit.example-site',
    title: static fn (): string => __('capell-demo-kit::welcome_tour.example_site_title'),
    description: static fn (): string => e(__('capell-demo-kit::welcome_tour.example_site_description')),
    element: '#demo-kit-example-site',
    icon: 'heroicon-o-sparkles',
    iconColor: 'success',
    sort: 80,
    visible: true,
);
```

Keep selectors stable. A missing selector means the step cannot anchor to the UI element.

## Verification

```bash
vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml
```
