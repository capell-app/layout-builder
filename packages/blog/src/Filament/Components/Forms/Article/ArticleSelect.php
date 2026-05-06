<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms\Article;

use Capell\Blog\Models\Article;
use Closure;
use Filament\FormBuilder\Components\MorphToSelect;
use Filament\FormBuilder\Components\MorphToSelect\Type;

class ArticleSelect extends MorphToSelect
{
    protected ?Closure $modifyKeySelectOptionsQueryUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-blog::form.article'))
            ->types([
                Type::make(Article::class)
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
