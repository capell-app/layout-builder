<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Enums\CampaignWidgetComponentEnum;
use Capell\Campaigns\Filament\Configurators\Widgets\CampaignCtaBlockWidgetConfigurator;
use Capell\Campaigns\Filament\Configurators\Widgets\CampaignHeroWidgetConfigurator;
use Capell\Campaigns\Filament\Configurators\Widgets\CampaignLeadFormWidgetConfigurator;
use Capell\Campaigns\Support\LayoutPresets\CampaignLayoutPreset;
use Capell\Campaigns\Support\LayoutPresets\LeadGenerationPreset;
use Capell\Campaigns\Support\LayoutPresets\ProductLaunchPreset;
use Capell\Campaigns\Support\LayoutPresets\WebinarPreset;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\Mosaic\Models\Widget;
use Lorisleiva\Actions\Concerns\AsAction;

final class InstallCampaignLayoutsAction
{
    use AsAction;

    /**
     * @var array<string, array{name: string, component: CampaignWidgetComponentEnum, configurator: class-string, icon: string}>
     */
    private const WIDGET_DEFINITIONS = [
        'campaign-hero' => [
            'name' => 'Campaign hero',
            'component' => CampaignWidgetComponentEnum::CampaignHero,
            'configurator' => CampaignHeroWidgetConfigurator::class,
            'icon' => 'heroicon-o-megaphone',
        ],
        'campaign-cta-block' => [
            'name' => 'Campaign CTA block',
            'component' => CampaignWidgetComponentEnum::CampaignCtaBlock,
            'configurator' => CampaignCtaBlockWidgetConfigurator::class,
            'icon' => 'heroicon-o-cursor-arrow-rays',
        ],
        'campaign-lead-form' => [
            'name' => 'Campaign lead form',
            'component' => CampaignWidgetComponentEnum::CampaignLeadForm,
            'configurator' => CampaignLeadFormWidgetConfigurator::class,
            'icon' => 'heroicon-o-clipboard-document-list',
        ],
    ];

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function handle(bool $force = false): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->presets() as $preset) {
            $layout = Layout::query()->where('key', $preset->key())->first();

            if ($layout instanceof Layout && ! $force) {
                $result['skipped']++;

                continue;
            }

            $widgets = $this->widgetsForPreset($preset);

            Layout::query()->updateOrCreate(
                ['key' => $preset->key()],
                [
                    'name' => $preset->name(),
                    'group' => 'Campaigns',
                    'containers' => $this->containersForPreset($preset, $widgets),
                    'status' => true,
                ],
            );

            $layout instanceof Layout ? $result['updated']++ : $result['created']++;
        }

        return $result;
    }

    /**
     * @return array<int, CampaignLayoutPreset>
     */
    private function presets(): array
    {
        return [
            new LeadGenerationPreset,
            new ProductLaunchPreset,
            new WebinarPreset,
        ];
    }

    /**
     * @return array<string, Widget>
     */
    private function widgetsForPreset(CampaignLayoutPreset $preset): array
    {
        $widgets = [];
        $type = $this->campaignWidgetType();

        foreach ($preset->widgets() as $widgetDefinition) {
            $widgetType = $widgetDefinition['type'] ?? null;
            if (! is_string($widgetType)) {
                continue;
            }

            if (! isset(self::WIDGET_DEFINITIONS[$widgetType])) {
                continue;
            }

            $definition = self::WIDGET_DEFINITIONS[$widgetType];
            $widgetKey = $preset->key() . '-' . $widgetType;

            $widgets[$widgetType] = Widget::query()->updateOrCreate(
                ['key' => $widgetKey],
                [
                    'name' => $preset->name() . ' - ' . $definition['name'],
                    'type_id' => $type->getKey(),
                    'meta' => [
                        'component' => $definition['component'],
                    ],
                    'admin' => [
                        'configurator' => $definition['configurator']::getKey(),
                        'icon' => $definition['icon'],
                    ],
                    'status' => true,
                ],
            );
        }

        return $widgets;
    }

    /**
     * @param  array<string, Widget>  $widgets
     * @return array<string, array{widgets: array<int, array{widget_key: string, occurrence: int}>, meta: array<string, mixed>}>
     */
    private function containersForPreset(CampaignLayoutPreset $preset, array $widgets): array
    {
        $containers = [];

        foreach ($preset->containers() as $containerDefinition) {
            $containerKey = $containerDefinition['key'] ?? null;
            if (! is_string($containerKey)) {
                continue;
            }

            if ($containerKey === '') {
                continue;
            }

            $containers[$containerKey] = [
                'widgets' => [],
                'meta' => [
                    'container' => $containerDefinition['width'] ?? null,
                ],
            ];
        }

        foreach ($preset->widgets() as $widgetDefinition) {
            $containerKey = $widgetDefinition['container'] ?? null;
            $widgetType = $widgetDefinition['type'] ?? null;
            if (! is_string($containerKey)) {
                continue;
            }

            if (! isset($containers[$containerKey])) {
                continue;
            }

            if (! is_string($widgetType)) {
                continue;
            }

            if (! isset($widgets[$widgetType])) {
                continue;
            }

            $containers[$containerKey]['widgets'][] = [
                'widget_key' => $widgets[$widgetType]->key,
                'occurrence' => 1,
            ];
        }

        return $containers;
    }

    private function campaignWidgetType(): Type
    {
        return Type::query()->firstOrCreate(
            [
                'key' => 'campaign',
                'type' => LayoutTypeEnum::Widget,
            ],
            [
                'name' => __('capell-campaigns::generic.campaign'),
                'group' => 'campaigns',
                'admin' => [
                    'type_configurator' => WidgetTypeConfigurator::getKey(),
                    'icon' => 'heroicon-o-megaphone',
                ],
                'meta' => [
                    'component' => CampaignWidgetComponentEnum::CampaignHero,
                ],
            ],
        );
    }
}
