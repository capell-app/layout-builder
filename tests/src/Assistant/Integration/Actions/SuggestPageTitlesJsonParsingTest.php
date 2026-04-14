<?php

declare(strict_types=1);

use Capell\Assistant\Actions\SuggestPageTitlesAction;
use Capell\Assistant\Support\Context\ContentActionContext;
use OpenAI\Laravel\Facades\OpenAI;

uses()->group('admin-ai');

it('parses JSON-formatted title suggestions', function (): void {
    OpenAI::swap(new class
    {
        private readonly object $chat;

        public function __construct()
        {
            $this->chat = new class
            {
                public function create(array $params): stdClass
                {
                    // Return JSON array string
                    return (object) [
                        'choices' => [
                            (object) ['message' => (object) ['content' => json_encode([
                                'Awesome Laravel Guide',
                                'Practical PHP Tips',
                                'Mastering Eloquent',
                            ], JSON_THROW_ON_ERROR)], 'finish_reason' => 'stop'],
                        ],
                        'usage' => (object) ['total_tokens' => 30, 'prompt_tokens' => 10, 'completion_tokens' => 20],
                    ];
                }
            };
        }

        public function chat(): object
        {
            return $this->chat;
        }
    });

    $context = new ContentActionContext(content: 'Laravel development tips', keywords: 'laravel, php', pageId: 1, pageType: 'page', languageId: 1);
    $titles = SuggestPageTitlesAction::run($context);

    expect($titles)->toBeArray();
    expect($titles)->toHaveCount(3);
    expect($titles[0])->toBe('Awesome Laravel Guide');
    expect($titles[1])->toBe('Practical PHP Tips');
    expect($titles[2])->toBe('Mastering Eloquent');
});
