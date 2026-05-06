<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Knowledge;

use Capell\AgentBridge\Support\KnowledgeRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\AgentBridge\Request;
use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\ResponseFactory;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Tool;
use Laravel\AgentBridge\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-knowledge-recommend-packages')]
#[Title('Recommend Capell Packages')]
#[Description('Recommend Capell packages for a short capability request.')]
#[IsReadOnly]
final class RecommendPackagesTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Capability or problem statement, such as SEO audits, redirects, form-builder, or publishing-studio.')->required(),
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
                ], static fn (string $value): bool => $value !== '')));

                $score = 0;
                $terms = preg_split('/\s+/', $query);
                $terms = $terms === false ? [] : $terms;

                foreach ($terms as $term) {
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
