<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\InternalLinkSuggestionData;
use Capell\SeoSuite\Support\InternalLinks\InternalLinkCandidateRepository;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static list<InternalLinkSuggestionData> run(Page $page, Site $site, Language $language)
 */
final class SuggestInternalLinksAction
{
    use AsAction;

    public function __construct(
        private readonly InternalLinkCandidateRepository $candidates,
    ) {}

    /**
     * @return list<InternalLinkSuggestionData>
     */
    public function handle(Page $page, Site $site, Language $language): array
    {
        $page->load([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
        ]);

        $sourceTokens = $this->tokensForPage($page);

        if ($sourceTokens === []) {
            return [];
        }

        return collect($this->candidates->forPage($page, $site, $language))
            ->map(fn (array $candidate): array => [
                ...$candidate,
                'score' => $this->scoreCandidate($sourceTokens, $candidate),
            ])
            ->filter(fn (array $candidate): bool => $candidate['score'] > 0)
            ->sort(function (array $firstCandidate, array $secondCandidate): int {
                $scoreComparison = $secondCandidate['score'] <=> $firstCandidate['score'];

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return strcasecmp($firstCandidate['title'], $secondCandidate['title']);
            })
            ->take(5)
            ->map(fn (array $candidate): InternalLinkSuggestionData => new InternalLinkSuggestionData(
                pageId: $candidate['page_id'],
                title: $candidate['title'],
                url: $candidate['url'],
                score: $candidate['score'],
                reason: 'Matches page topic: ' . $candidate['title'],
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function tokensForPage(Page $page): array
    {
        $translation = $page->translation;

        if (! $translation instanceof Translation) {
            return [];
        }

        return $this->tokenize(implode(' ', array_filter([
            $this->stringValue($translation->title),
            $this->stringValue($translation->meta_description),
            $this->contentValue($translation->content),
        ], static fn (?string $contentPart): bool => $contentPart !== null && $contentPart !== '')));
    }

    /**
     * @param  list<string>  $sourceTokens
     * @param  array{page_id:int,title:string,url:string,meta_title:?string,meta_description:?string}  $candidate
     */
    private function scoreCandidate(array $sourceTokens, array $candidate): int
    {
        return ($this->matchingTokenCount($sourceTokens, $candidate['title']) * 5)
            + ($this->matchingTokenCount($sourceTokens, $candidate['meta_title']) * 3)
            + ($this->matchingTokenCount($sourceTokens, $candidate['meta_description']));
    }

    /**
     * @param  list<string>  $sourceTokens
     */
    private function matchingTokenCount(array $sourceTokens, ?string $text): int
    {
        if ($text === null) {
            return 0;
        }

        $candidateTokens = $this->tokenize($text);

        return count(array_intersect($sourceTokens, $candidateTokens));
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(strip_tags($text)));

        if ($tokens === false) {
            return [];
        }

        return collect($tokens)
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => mb_strlen($token) >= 4)
            ->unique()
            ->values()
            ->all();
    }

    private function contentValue(mixed $content): ?string
    {
        if (is_scalar($content)) {
            return (string) $content;
        }

        if ($content === null) {
            return null;
        }

        $jsonContent = json_encode($content);

        return is_string($jsonContent) ? $jsonContent : null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $stringValue = trim(strip_tags((string) $value));

        return $stringValue !== '' ? $stringValue : null;
    }
}
