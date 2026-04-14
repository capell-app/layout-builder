<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms\Article;

use Capell\Blog\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Closure;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;

class ArticleSelect extends MorphToSelect
{
    protected ?Closure $modifyKeySelectOptionsQueryUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-blog::form.article'))
            ->types([
                Type::make(CapellCore::getModel(ModelEnum::Article))
                    ->titleAttribute('name')
                    ->modifyOptionsQueryUsing($this->modifyKeySelectOptionsQueryUsing),
            ]);
    }

    public function modifyKeySelectOptionsQueryUsing(Closure $closure): self
    {
        $this->modifyKeySelectOptionsQueryUsing = $closure;

        return $this;
    }
}
