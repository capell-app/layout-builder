<?php

declare(strict_types=1);

namespace Capell\Blog\Listeners;

use Capell\Admin\Actions\AddPageToNavigationAction;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Enums\DefaultNavigationEnum;
use Capell\Core\Events\NavigationCreating;

class AddBlogPagesToNavigation
{
    private array $handles = [
        DefaultNavigationEnum::Main->value,
        DefaultNavigationEnum::Footer->value,
    ];

    public function handle(NavigationCreating $event): void
    {
        if (! in_array($event->navigation->handle, $this->handles, true)) {
            return;
        }

        $blogPage = BlogLoader::getBlogPage($event->navigation->site);

        if ($blogPage) {
            AddPageToNavigationAction::run($blogPage, $event->navigation);
        }
    }
}
