<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Knowledge;

use Capell\Mcp\Support\KnowledgeRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-knowledge-recommend-packages')]
#[Title('Recommend Capell Packages')]
#[Description('Recommend Capell packages for a short capability request.')]
#[IsReadOnly]
final class RecommendPackagesTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Capability or problem statement, such as SEO audits, redirects, forms, or workspaces.')->required(),
        ];
    }

    public function handle(Request $request, KnowledgeRepository $repository): ResponseFactory
    {
        $data = $request->validate([
            'query' => ['required', 'string'],
        ]);

        $query = strtolower((string) $data['query']);
        $recommendations = collect($repository->packages())
            ->map(function (array $package) use ($query): array {
                $haystack = strtolower(implode(' ', array_filter([
                    $package['name'] ?? '',
                    $package['productGroup'] ?? '',
                    $package['bundle'] ?? '',
                    implode(' ', $package['contexts'] ?? []),
                ])));

                $score = 0;
                foreach (preg_split('/\s+/', $query) ?: [] as $term) {
                    if ($term !== '' && str_contains($haystack, $term)) {
                        $score++;
                    }
                }

                return [
                    ...$package,
                    'score' => $score,
                ];
            })
            ->filter(fn (array $package): bool => (int) $package['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->take(8)
            ->all();

        return Response::structured([
            'query' => $query,
            'recommendations' => $recommendations,
        ]);
    }
}
