<?php

declare(strict_types=1);

namespace Capell\Search\Http\Controllers;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Facades\Frontend;
use Capell\Search\Actions\RecordSearchAction;
use Capell\Search\Actions\ResolveSearchSettingAction;
use Capell\Search\Actions\RunSearchAction;
use Capell\Search\Data\SearchRequestData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Throwable;

final class SearchController
{
    public function __invoke(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) ResolveSearchSettingAction::run(
            'results_per_page',
            'capell-search.results_per_page',
            10,
        );

        $site = $request->attributes->get('site');
        $language = $request->attributes->get('language');

        $data = new SearchRequestData(
            query: $query,
            page: $page,
            perPage: $perPage,
            siteId: is_object($site) ? (int) data_get($site, 'id') : null,
            languageId: is_object($language) ? (int) data_get($language, 'id') : null,
        );

        $results = RunSearchAction::run($data);

        RecordSearchAction::run($data, $results->total(), $request);

        $content = view('capell-search::pages.search', [
            'query' => $query,
            'results' => $results,
        ]);

        if (! $this->canRenderFrontendShell()) {
            return $content;
        }

        $slot = view('capell-search::layouts.frontend', [
            'query' => $query,
            'results' => $results,
        ]);

        return view('capell::app', [
            'language' => Frontend::language(),
            'layout' => Frontend::layout(),
            'pageRecord' => Frontend::page(),
            'site' => Frontend::site(),
            'slot' => new HtmlString($slot->render()),
            'theme' => Frontend::theme(),
        ]);
    }

    private function canRenderFrontendShell(): bool
    {
        try {
            return Frontend::language() instanceof Language
                && Frontend::layout() instanceof Layout
                && Frontend::page() instanceof Pageable
                && Frontend::site() instanceof Site
                && Frontend::theme() instanceof Theme;
        } catch (Throwable) {
            return false;
        }
    }
}
