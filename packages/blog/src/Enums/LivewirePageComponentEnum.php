<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Livewire\Page\Archive;
use Capell\Blog\Livewire\Page\Blog;
use Capell\Blog\Livewire\Page\Tag;
use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;

enum LivewirePageComponentEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(Archive::class)]
    case ArchivePage = 'capell-blog::page.archive';

    #[Component(Blog::class)]
    case BlogPage = 'capell-blog::page.blog';

    #[Component(Tag::class)]
    case TagPage = 'capell-blog::page.tag';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }

    public function getComponent(): ?string
    {
        return $this->getCaseAttribute(Component::class)?->class;
    }
}
