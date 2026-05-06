<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data\Ai;

use Capell\SeoSuite\Models\AIGenerationHistory;
use Capell\SeoSuite\Support\AiResponse;
use Spatie\LaravelData\Data;

class AiGenerationResultData extends Data
{
    /**
     * @param  array<int, array{role: string, content: string}>|null  $messages
     * @param  array<string, mixed>|null  $params
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $actionKey,
        public mixed $output,
        public string $inputText,
        public string $outputText,
        public ?AiResponse $response = null,
        public ?array $messages = null,
        public ?array $params = null,
        public int|string|null $pageableId = null,
        public ?string $pageableType = null,
        public ?int $languageId = null,
        public array $metadata = [],
        public ?int $aiCreatorSessionId = null,
        public ?AIGenerationHistory $history = null,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>|null  $messages
     * @param  array<string, mixed>|null  $params
     * @param  array<string, mixed>  $metadata
     */
    public static function make(
        string $actionKey,
        mixed $output,
        string $inputText,
        string $outputText,
        ?AiResponse $response = null,
        ?array $messages = null,
        ?array $params = null,
        int|string|null $pageableId = null,
        ?string $pageableType = null,
        ?int $languageId = null,
        array $metadata = [],
        ?int $aiCreatorSessionId = null,
    ): self {
        return new self(
            actionKey: $actionKey,
            output: $output,
            inputText: $inputText,
            outputText: $outputText,
            response: $response,
            messages: $messages,
            params: $params,
            pageableId: $pageableId,
            pageableType: $pageableType,
            languageId: $languageId,
            metadata: $metadata,
            aiCreatorSessionId: $aiCreatorSessionId,
        );
    }
}
