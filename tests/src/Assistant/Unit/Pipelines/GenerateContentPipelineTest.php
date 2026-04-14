<?php

declare(strict_types=1);

use Capell\Assistant\Data\PromptData;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Assistant\Support\OpenAIProvider;
use Capell\Assistant\Support\Pipelines\GenerateContentPipeline;
use Mockery\MockInterface;

it('sanitizes unsafe html from AI output', function (): void {
    $prompts = new PromptData(
        model: 'gpt-4-turbo',
        contentGeneration: true,
        contentGenerationSystem: 'system',
        contentGenerationUserTemplate: 'Title: {{current_title}} Keywords: {{keywords}} Existing: {{content}} Length: {{target_length}} Refactor: {{refactor}}',
    );

    $provider = mock(OpenAIProvider::class, function (OpenAIProvider&MockInterface $mock): void {
        $unsafe = <<<'HTML'
        <h2 onclick="alert('x')">Welcome</h2>
        <p>Visit our <a href="https://evil.example/" target="_blank" rel="noopener">site</a> or <a href="/about">about page</a>.</p>
        <p>Inline JS: <a href="javascript:alert('x')">click</a></p>
        <script>alert('xss')</script>
        <style>body{color:red}</style>
        HTML;

        $mock->shouldReceive('chat')->andReturn(new AiResponse(
            content: $unsafe,
            tokensUsed: 50,
            model: 'gpt-4-turbo',
            duration: 0.1,
            metadata: [],
        ));
    });

    $rateLimiter = mock(AiRateLimiter::class, function (AiRateLimiter&MockInterface $mock): void {
        $mock->shouldReceive('checkLimit')->andReturnTrue();
    });

    $pipeline = new GenerateContentPipeline($prompts, $provider, $rateLimiter);

    $context = new ContentActionContext(
        content: 'Existing content',
        keywords: 'foo,bar',
        pageId: 1,
        pageType: 'page',
        languageId: 1,
    );

    $result = $pipeline->execute([
        'context' => $context,
        'options' => [
            'user_id' => 123,
            'current_title' => 'Test',
            'target_length' => 500,
            'refactor' => true,
        ],
        'action' => $pipeline,
    ]);

    expect($result)
        ->not()->toContain('<script')
        ->not()->toContain('<style')
        ->not()->toContain('javascript:')
        ->not()->toContain('onclick=')
        ->toContain('<a href="/about">')
        ->toContain('<a href="https://evil.example/" target="_blank" rel="noopener">site</a>');
});

it('renders prompt variables correctly', function (): void {
    $prompts = new PromptData(
        model: 'gpt-4-turbo',
        contentGeneration: true,
        contentGenerationSystem: 'system',
        contentGenerationUserTemplate: 'Title: {{current_title}} Keywords: {{keywords}} Existing: {{content}} Length: {{target_length}} Refactor: {{refactor}}',
    );

    $provider = mock(OpenAIProvider::class, function (OpenAIProvider&MockInterface $mock): void {
        $mock->shouldReceive('chat')->withArgs(function (array $params): bool {
            $user = collect($params['messages'])->firstWhere('role', 'user')['content'] ?? '';

            return str_contains($user, 'Title: Test')
                && str_contains($user, 'Keywords: foo,bar')
                && str_contains($user, 'Existing: Existing content')
                && str_contains($user, 'Length: 500')
                && str_contains($user, 'Refactor: yes');
        })->andReturn(new AiResponse(
            content: '<p>OK</p>',
            tokensUsed: 10,
            model: 'gpt-4-turbo',
            duration: 0.1,
            metadata: [],
        ));
    });

    $rateLimiter = mock(AiRateLimiter::class, function (AiRateLimiter&MockInterface $mock): void {
        $mock->shouldReceive('checkLimit')->andReturnTrue();
    });

    $pipeline = new GenerateContentPipeline($prompts, $provider, $rateLimiter);

    $context = new ContentActionContext(content: 'Existing content', keywords: 'foo,bar', pageId: 1, pageType: 'page', languageId: 1);

    $result = $pipeline->execute([
        'context' => $context,
        'options' => [
            'user_id' => 123,
            'current_title' => 'Test',
            'target_length' => 500,
            'refactor' => true,
        ],
        'action' => $pipeline,
    ]);

    expect($result)->toBe('<p>OK</p>');
});
