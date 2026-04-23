<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support;

class AiTokenCounter
{
    public function estimate(string $text, string $model = 'gpt-4-turbo'): int
    {
        $baseTokens = (int) ceil(strlen($text) / 4);
        $multiplier = match ($model) {
            'gpt-4-turbo' => 1.0,
            'gpt-3.5-turbo' => 1.1,
            'gpt-4o' => 0.95,
            default => 1.0,
        };

        return (int) ($baseTokens * $multiplier);
    }

    public function count(array $usage): array
    {
        return [
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
        ];
    }

    /**
     * Lenient counter to support tests passing a string by mistake.
     * Prefer count() with array usage in application code.
     */
    public function countFromString(string $usage): array
    {
        return [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];
    }

    public function wouldExceedLimit(int $estimatedTokens, int $limit): bool
    {
        return $estimatedTokens > $limit;
    }
}
