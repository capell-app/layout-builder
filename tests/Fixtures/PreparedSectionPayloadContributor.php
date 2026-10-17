<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Support\Security\PublicHtmlSanitizer;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use Override;

final class PreparedSectionPayloadContributor implements PublicLayoutWidgetPayloadContributor
{
    public const string KEY_PREFIX = 'prepared-section-';

    public const string INTRODUCTION_WIDGET_KEY = self::KEY_PREFIX . 'structured-text';

    #[Override]
    public function priority(): int
    {
        return 50;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        $html = $this->html($widget, $page, $language, $containerKey, $occurrence);

        if ($html === null) {
            return [];
        }

        return [
            'widget_key' => $widget->key,
            'content' => '',
            'sections' => [['order' => $occurrence, 'html' => $this->introduction($widget, $page) . $html]],
        ];
    }

    #[Override]
    public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        if (! str_starts_with($widget->key, self::KEY_PREFIX)) {
            return null;
        }

        return '<section class="prepared-section-reference" data-source="' . e($widget->key) . '"><p>Prepared section output.</p></section>';
    }

    private function introduction(Widget $widget, Page $page): string
    {
        $translation = $page->relationLoaded('translation') ? $page->getRelation('translation') : null;

        if ($widget->key !== self::INTRODUCTION_WIDGET_KEY || ! $translation instanceof Model) {
            return '';
        }

        $content = $translation->getAttribute('content');

        return resolve(PublicHtmlSanitizer::class)->sanitize(is_string($content) ? $content : '');
    }
}
