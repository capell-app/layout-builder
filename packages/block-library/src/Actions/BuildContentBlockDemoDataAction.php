<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;
use stdClass;

/**
 * @method static array{definition: ContentBlockDefinitionData, asset: stdClass, title: string, summary: string, meta: array<string, mixed>, linkText: string|null, url: string|null} run(string $key)
 */
class BuildContentBlockDemoDataAction
{
    use AsObject;

    public function handle(string $key): array
    {
        $registry = resolve(ContentBlockRegistry::class);

        if ($registry->all() === []) {
            RegisterDefaultBlockLibraryAction::run($registry);
        }

        $definition = $registry->get($key);

        if (! $definition instanceof ContentBlockDefinitionData) {
            throw new InvalidArgumentException(sprintf('Content block [%s] is not registered.', $key));
        }

        $asset = new stdClass;
        $asset->name = $definition->label;

        return [
            'definition' => $definition,
            'asset' => $asset,
            'title' => $definition->label,
            'summary' => $definition->description,
            'meta' => $this->meta($definition->key),
            'linkText' => $definition->key === 'call_to_action' ? 'Start a project' : null,
            'url' => $definition->key === 'call_to_action' ? '#' : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(string $key): array
    {
        return match ($key) {
            'accordion' => [
                'items' => [
                    ['heading' => 'How quickly can editors update content?', 'content' => '<p>Editors can update reusable panels once and reuse them across pages.</p>'],
                    ['heading' => 'Can panels be reordered?', 'content' => '<p>Yes. Repeaters support cloning, collapsing, and manual ordering.</p>'],
                ],
                'first_open' => true,
            ],
            'comparison' => [
                'columns' => [
                    ['heading' => 'Starter', 'description' => 'For small sites'],
                    ['heading' => 'Growth', 'description' => 'For busy teams', 'highlighted' => true],
                    ['heading' => 'Enterprise', 'description' => 'For complex estates'],
                ],
                'rows' => [
                    ['label' => 'Reusable blocks', 'values' => '12|Unlimited|Unlimited'],
                    ['label' => 'Editor roles', 'values' => 'Basic|Advanced|Custom'],
                    ['label' => 'Support', 'values' => 'Community|Priority|Dedicated'],
                ],
            ],
            'counter' => [
                'counters' => [
                    ['value' => '42', 'suffix' => '%', 'label' => 'Faster publishing', 'description' => 'Average reduction in edit-to-live time.', 'icon' => 'heroicon-o-bolt'],
                    ['prefix' => '+', 'value' => '18', 'label' => 'Reusable sections', 'description' => 'Popular block patterns ready for editors.', 'icon' => 'heroicon-o-square-3-stack-3d'],
                    ['value' => '99.9', 'suffix' => '%', 'label' => 'Availability', 'description' => 'Designed for production CMS workloads.', 'icon' => 'heroicon-o-chart-bar'],
                ],
                'animate' => true,
            ],
            'table' => [
                'caption' => 'Editorial workflow comparison',
                'headers' => [
                    ['label' => 'Workflow'],
                    ['label' => 'Owner'],
                    ['label' => 'SLA'],
                ],
                'rows' => [
                    ['cells' => 'Draft review|Content lead|1 day'],
                    ['cells' => 'Legal approval|Compliance|2 days'],
                    ['cells' => 'Publish|Editor|Same day'],
                ],
            ],
            'tabs' => [
                'tabs' => [
                    ['label' => 'Plan', 'icon' => 'heroicon-o-map', 'content' => '<p>Collect requirements and choose the content model.</p>'],
                    ['label' => 'Build', 'icon' => 'heroicon-o-wrench-screwdriver', 'content' => '<p>Create blocks, seed examples, and wire frontend rendering.</p>'],
                    ['label' => 'Publish', 'icon' => 'heroicon-o-rocket-launch', 'content' => '<p>Preview, approve, and ship the page with confidence.</p>'],
                ],
            ],
            'features' => [
                'features' => [
                    ['heading' => 'Reusable patterns', 'description' => 'Build once and place the block wherever it belongs.', 'icon' => 'heroicon-o-squares-2x2', 'url' => '#'],
                    ['heading' => 'Editor friendly', 'description' => 'Clear fields and labels keep daily publishing simple.', 'icon' => 'heroicon-o-pencil-square', 'url' => '#'],
                    ['heading' => 'Frontend ready', 'description' => 'Renderers expose the fields editors configure.', 'icon' => 'heroicon-o-computer-desktop', 'url' => '#'],
                ],
                'columns' => '3',
            ],
            'logos' => [
                'logos' => [
                    ['name' => 'Northstar', 'url' => '#'],
                    ['name' => 'Brightline', 'url' => '#'],
                    ['name' => 'Oak & Co', 'url' => '#'],
                    ['name' => 'Signal Works', 'url' => '#'],
                ],
                'columns' => '4',
            ],
            'pricing' => [
                'plans' => [
                    ['name' => 'Launch', 'price' => '$49', 'period' => 'mo', 'description' => 'For small editorial teams.', 'features' => "Core blocks\nBasic publishing\nEmail support", 'action_label' => 'Choose Launch', 'action_url' => '#'],
                    ['name' => 'Scale', 'price' => '$149', 'period' => 'mo', 'description' => 'For growing content operations.', 'features' => "All blocks\nApprovals\nPriority support", 'action_label' => 'Choose Scale', 'action_url' => '#', 'highlighted' => true],
                    ['name' => 'Enterprise', 'price' => 'Custom', 'description' => 'For multi-site CMS programmes.', 'features' => "Custom workflows\nDedicated support\nSecurity review", 'action_label' => 'Talk to us', 'action_url' => '#'],
                ],
            ],
            'stats' => [
                'stats' => [
                    ['value' => '18', 'label' => 'Block types', 'description' => 'Useful defaults without a bloated package.'],
                    ['value' => '4', 'label' => 'Editor states', 'description' => 'Draft, preview, approve, and publish flows.'],
                    ['value' => '100%', 'label' => 'Reusable', 'description' => 'Blocks can be shared across pages.'],
                    ['value' => '0', 'label' => 'Pro bundle', 'description' => 'Advanced blocks stay in optional packages.'],
                ],
                'columns' => '4',
            ],
            'team' => [
                'members' => [
                    ['name' => 'Alex Morgan', 'role' => 'Content Lead', 'bio' => 'Owns the editorial model and publishing standards.', 'url' => '#'],
                    ['name' => 'Priya Shah', 'role' => 'UX Designer', 'bio' => 'Turns reusable blocks into clear content patterns.', 'url' => '#'],
                    ['name' => 'Jamie Lee', 'role' => 'Engineer', 'bio' => 'Keeps the admin and frontend rendering dependable.', 'url' => '#'],
                ],
                'columns' => '3',
            ],
            'timeline' => [
                'milestones' => [
                    ['date' => 'Week 1', 'heading' => 'Audit', 'description' => 'Identify reusable content patterns.'],
                    ['date' => 'Week 2', 'heading' => 'Configure', 'description' => 'Create types and editor fields.'],
                    ['date' => 'Week 3', 'heading' => 'Launch', 'description' => 'Capture screenshots and verify frontend output.'],
                ],
            ],
            'faq' => [
                'questions' => [
                    ['question' => 'Can FAQ content be reused?', 'answer' => '<p>Yes. The block stores reusable question and answer pairs.</p>'],
                    ['question' => 'Is it separate from Accordion?', 'answer' => '<p>It shares the same pattern but uses FAQ-oriented labels and defaults.</p>'],
                ],
                'first_open' => true,
            ],
            'divider' => [
                'style' => 'dots',
                'spacing' => 'md',
            ],
            'call_to_action' => [
                'alignment' => 'center',
                'color' => 'primary',
                'actions' => [
                    ['label' => 'Start a project', 'url' => '#', 'style' => 'primary'],
                    ['label' => 'View examples', 'url' => '#', 'style' => 'secondary'],
                ],
            ],
            'hero' => [
                'alignment' => 'center',
                'color' => 'primary',
            ],
            'testimonial' => [
                'quote' => 'Capell gives our editors the right amount of structure without slowing them down.',
                'author' => 'Morgan Ellis',
                'role' => 'Digital Director',
            ],
            default => [],
        };
    }
}
