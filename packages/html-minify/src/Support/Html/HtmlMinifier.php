<?php

declare(strict_types=1);

namespace Capell\HtmlMinify\Support\Html;

use Capell\Frontend\Contracts\HtmlMinifier as HtmlMinifierContract;
use voku\helper\HtmlMin;

final class HtmlMinifier implements HtmlMinifierContract
{
    public function minify(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $htmlMin = new HtmlMin;

        $htmlMin->doOptimizeAttributes(false);
        $htmlMin->doSortHtmlAttributes(false);
        $htmlMin->doSortCssClassNames(false);
        $htmlMin->doRemoveOmittedHtmlTags(false);
        $htmlMin->doRemoveOmittedQuotes(false);

        return $htmlMin->minify($html);
    }
}
