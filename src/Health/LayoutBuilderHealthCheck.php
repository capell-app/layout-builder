<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Layout;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Models\WidgetWidget;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Throwable;

final class LayoutBuilderHealthCheck implements ChecksExtensionHealth
{
    /**
     * Livewire alias the dual-mode layout editor is registered under.
     */
    private const string EDITOR_LIVEWIRE_ALIAS = 'capell-layout-builder::filament.layout-builder';

    /**
     * @var list<class-string<Model>>
     */
    private const array STORAGE_MODELS = [
        Layout::class,
        Widget::class,
        WidgetAsset::class,
        WidgetWidget::class,
        LayoutPreset::class,
        LayoutBulkChangeRun::class,
        LayoutBulkChangeResult::class,
    ];

    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $check = new self;

        return collect([
            $check->storageTablesCheck(),
            $check->publicLayoutGraphBuilderBindingCheck(),
            $check->editorComponentRegistrationCheck(),
        ]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    /**
     * Asserts every layout storage table exists.
     */
    public function storageTablesCheck(): DoctorCheckResultData
    {
        $missingTables = $this->missingTables();

        return new DoctorCheckResultData(
            label: 'Layout Builder storage tables',
            passed: $missingTables === [],
            message: $missingTables === []
                ? 'The layout, widget, preset, and bulk-change storage tables are present.'
                : 'Missing tables: ' . implode(', ', $missingTables) . '.',
            remediation: $missingTables === []
                ? null
                : 'Run the Capell migrations to create the Layout Builder storage tables.',
        );
    }

    /**
     * Asserts the public layout graph builder contract is bound to the package implementation.
     */
    public function publicLayoutGraphBuilderBindingCheck(): DoctorCheckResultData
    {
        $isBound = $this->publicLayoutGraphBuilderIsBound();

        return new DoctorCheckResultData(
            label: 'Layout Builder public graph builder binding',
            passed: $isBound,
            message: $isBound
                ? 'The public layout graph builder contract resolves to the Layout Builder implementation.'
                : 'The public layout graph builder contract is not bound to the Layout Builder implementation.',
            remediation: $isBound
                ? null
                : 'Ensure LayoutBuilderServiceProvider binds PublicLayoutGraphBuilder to LayoutBuilderPublicLayoutGraphBuilder.',
        );
    }

    /**
     * Asserts the dual-mode editor Livewire component is registered.
     */
    public function editorComponentRegistrationCheck(): DoctorCheckResultData
    {
        $isRegistered = $this->editorComponentIsRegistered();

        return new DoctorCheckResultData(
            label: 'Layout Builder editor component registration',
            passed: $isRegistered,
            message: $isRegistered
                ? 'The Layout Builder editor Livewire component is registered.'
                : 'The Layout Builder editor Livewire component is not registered.',
            remediation: $isRegistered
                ? null
                : 'Ensure LayoutBuilderAdminRegistrar registers the ' . self::EDITOR_LIVEWIRE_ALIAS . ' Livewire component.',
        );
    }

    /**
     * @return list<string>
     */
    public function missingTables(): array
    {
        return array_values(collect($this->requiredTableNames())
            ->reject(static fn (string $tableName): bool => Schema::hasTable($tableName))
            ->values()
            ->all());
    }

    public function publicLayoutGraphBuilderIsBound(): bool
    {
        if (! app()->bound(PublicLayoutGraphBuilder::class)) {
            return false;
        }

        try {
            $resolved = resolve(PublicLayoutGraphBuilder::class);
        } catch (Throwable) {
            return false;
        }

        return $resolved instanceof LayoutBuilderPublicLayoutGraphBuilder;
    }

    public function editorComponentIsRegistered(): bool
    {
        try {
            if (Livewire::exists(self::EDITOR_LIVEWIRE_ALIAS)) {
                return true;
            }

            return (bool) Livewire::exists(LayoutBuilder::class);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function requiredTableNames(): array
    {
        $tableNames = [];

        foreach (self::STORAGE_MODELS as $modelClass) {
            $tableNames[] = (new $modelClass)->getTable();
        }

        return array_values(array_unique($tableNames));
    }
}
