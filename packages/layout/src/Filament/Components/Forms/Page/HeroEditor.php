<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Page;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Filament\Schemas\Components\Group;

class HeroEditor extends Group
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('meta')
            ->visible(
                function (null|PageTranslation|Page $record): bool {
                    $layoutId = $this->getRootContainer()->getRawState()['layout_id'] ?? null;

                    if ($record !== null) {
                        $layoutId = $record instanceof PageTranslation
                            ? $record->page->layout_id
                            : $record->layout_id;
                    }

                    if (! $layoutId) {
                        return false;
                    }

                    $layout = $this->getLayout((int) $layoutId);

                    return in_array('hero', $layout->widgets, true);
                }
            )
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-layout::form.hero'))
                    ->hint(__('capell-layout::generic.hero_info')),
            ]);
    }

    protected function getLayout(int $layoutId): ?Layout
    {
        return once(fn (): ?Layout => CapellCore::getModel(ModelEnum::Layout)::find($layoutId));
    }
}
