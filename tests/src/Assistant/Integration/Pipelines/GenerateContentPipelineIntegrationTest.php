<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GeneratorPageContentAction;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Support\Context\ContentActionContext;
use OpenAI\Laravel\Facades\OpenAI;

uses()->group('admin-ai');

it('records AIGenerationHistory with metadata after generation', function (): void {
    OpenAI::swap(new class
    {
        private readonly object $chat;

        public function __construct()
        {
            $this->chat = new class
            {
                public function create(array $params): stdClass
                {
                    return (object) [
                        'choices' => [
                            (object) ['message' => (object) ['content' => "# Title\n\nContent"], 'finish_reason' => 'stop'],
                        ],
                        'usage' => (object) ['total_tokens' => 50, 'prompt_tokens' => 25, 'completion_tokens' => 25],
                    ];
                }
            };
        }

        public function chat(): object
        {
            return $this->chat;
        }
    });

    $context = new ContentActionContext(content: 'C', keywords: 'K', pageId: 123, pageType: 'page', languageId: 9);
    $draft = GeneratorPageContentAction::run($context, ['user_id' => 99]);

    expect($draft)->toBeString();

    /** @var AIGenerationHistory|null $record */
    $record = AIGenerationHistory::query()->latest('id')->first();

    expect($record)->not()->toBeNull()
        ->and($record->metadata)->toBeArray()
        ->and($record->metadata)->not()->toBeEmpty()
        ->and($record->pageable_type)->toBe('page')
        ->and($record->pageable_id)->toBe(123)
        ->and($record->language_id)->toBe(9);

    // Assert persisted columns for page and language identifiers when available
});
