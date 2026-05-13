# Command Palette

Diagnostics can expose safe operational shortcuts through command palette providers. Providers return command metadata; execution and authorization stay in the Diagnostics package.

## Register a Provider

Implement `CommandPaletteProvider` and tag it as `capell.diagnostics.command-palette-provider`. Each returned key should be stable because command runs are persisted.

```php
use Capell\Diagnostics\Contracts\CommandPaletteProvider;
use Capell\Diagnostics\Data\CommandPaletteCommandData;
use Capell\Diagnostics\Enums\CommandPaletteDanger;
use Capell\Diagnostics\Enums\CommandPaletteType;

final class DemoPaletteProvider implements CommandPaletteProvider
{
    public function commandPaletteCommands(): array
    {
        return [
            'demo.clear-cache' => new CommandPaletteCommandData(
                id: 'demo.clear-cache',
                label: __('host-app::diagnostics.clear_demo_cache'),
                type: CommandPaletteType::Artisan,
                description: __('host-app::diagnostics.clear_demo_cache_description'),
                command: 'cache:clear',
                ability: 'run_diagnostics_commands',
                danger: CommandPaletteDanger::Safe,
                requiresConfirmation: true,
                keywords: ['cache', 'demo'],
                group: 'Demo',
            ),
        ];
    }
}

$this->app->singleton(DemoPaletteProvider::class);
$this->app->tag([DemoPaletteProvider::class], 'capell.diagnostics.command-palette-provider');
```

Use host-package translations for labels and descriptions. Do not put user-facing text directly in provider classes.

## Command Data

| Field                  | Use                                             |
| ---------------------- | ----------------------------------------------- |
| `id`                   | Stable command identifier.                      |
| `type`                 | Command type, such as link or Artisan command.  |
| `command`              | Command string for command-backed entries.      |
| `ability`              | Authorization ability checked before execution. |
| `danger`               | Risk level shown in the UI.                     |
| `requiresConfirmation` | Adds a confirmation step before execution.      |
| `parameters`           | Parameter definitions for command input.        |
| `keywords`             | Search terms for palette filtering.             |

Keep dangerous commands out unless they have explicit authorization and confirmation.

## Verification

```bash
vendor/bin/pest packages/diagnostics/tests --configuration=phpunit.xml
```
