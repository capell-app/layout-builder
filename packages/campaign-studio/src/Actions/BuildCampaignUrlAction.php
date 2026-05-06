<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Data\UtmData;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildCampaignUrlAction
{
    use AsAction;

    public function handle(string $url, UtmData $utm): string
    {
        $utmParameters = array_filter([
            'utm_source' => $utm->source,
            'utm_medium' => $utm->medium,
            'utm_campaign' => $utm->campaign,
            'utm_term' => $utm->term,
            'utm_content' => $utm->content,
        ], fn (?string $value): bool => is_string($value) && trim($value) !== '');

        if ($utmParameters === []) {
            return $url;
        }

        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $urlWithoutFragment = $fragment === null ? $url : str_replace('#' . $fragment, '', $url);
        $query = parse_url($urlWithoutFragment, PHP_URL_QUERY);
        $baseUrl = $query === null ? $urlWithoutFragment : mb_substr($urlWithoutFragment, 0, -mb_strlen('?' . $query));
        $queryParameters = [];

        if (is_string($query) && $query !== '') {
            parse_str($query, $queryParameters);
        }

        foreach ($utmParameters as $key => $value) {
            if (! array_key_exists($key, $queryParameters)) {
                $queryParameters[$key] = $value;
            }
        }

        $rebuiltUrl = $baseUrl;

        if ($queryParameters !== []) {
            $rebuiltUrl .= '?' . http_build_query($queryParameters);
        }

        if (is_string($fragment) && $fragment !== '') {
            $rebuiltUrl .= '#' . $fragment;
        }

        return $rebuiltUrl;
    }
}
