<?php

declare(strict_types=1);

use Capell\Assistant\Actions\SuggestMetaDescriptionsAction;
use Capell\Assistant\Support\Context\ContentActionContext;
use OpenAI\Laravel\Facades\OpenAI;

uses()->group('admin-ai');

it('does not include page_id/language_id columns when sentinel IDs are used', function (): void {
    // Stub OpenAI
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
                        'choices' => [(object) ['message' => (object) ['content' => "- First\n- Second\n- Third"], 'finish_reason' => 'stop']],
                        'usage' => (object) ['total_tokens' => 20, 'prompt_tokens' => 8, 'completion_tokens' => 12],
                    ];
                }
            };
        }

        public function chat(): object
        {
            return $this->chat;
        }
    });

    $context = new ContentActionContext(content: 'Example content', keywords: 'keywords');
    $result = SuggestMetaDescriptionsAction::run($context);

    expect($result)->toBeArray()->and(count($result))->toBeGreaterThan(0);
});
