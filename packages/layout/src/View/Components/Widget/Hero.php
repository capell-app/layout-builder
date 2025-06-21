<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Core\Models\Media;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\MediaLoader;
use Illuminate\Database\Eloquent\Collection;

class Hero extends AbstractWidget
{
    protected ?Media $backgroundImage = null;

    protected ?Collection $contents = null;

    protected string $defaultView = 'capell::components.widget.hero.index';

    public function render(array $data = [])
    {
        return parent::render([
            ...$data,
            'backgroundImage' => $this->backgroundImage,
        ]);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::getPage();

        if (! empty($page->translation->meta['hero']['background_image_id'])) {
            $this->backgroundImage = MediaLoader::getMedia($page->translation->meta['hero']['background_image_id']);
        } elseif (! empty($this->widget->meta['background_image_id'])) {
            $this->backgroundImage = MediaLoader::getMedia($this->widget->meta['background_image_id']);
        }

        if (! $this->backgroundImage &&
            ! $this->widget->translation?->content &&
            empty($page->translation->meta['hero']) &&
            $this->widget->assets->isEmpty()
        ) {
            $this->skipRender = true;
        }
    }
}
