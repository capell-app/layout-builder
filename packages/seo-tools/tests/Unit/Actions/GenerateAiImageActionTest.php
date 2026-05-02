<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\GenerateAiImageAction;
use Capell\SeoTools\DataObjects\AiImageData;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\Cache\RateLimitCache;
use Prism\Prism\Enums\Provider;

function makeAiImageRateLimiter(?Throwable $exception = null): AiRateLimiter
{
    return new class($exception) extends AiRateLimiter
    {
        /** @var array<int, array{identifier: string, feature: string|null}> */
        public array $checks = [];

        public function __construct(private readonly ?Throwable $exception)
        {
            parent::__construct(new RateLimitCache('array'), ['enabled' => false]);
        }

        public function checkLimit(string $identifier = 'global', ?string $feature = null): void
        {
            $this->checks[] = ['identifier' => $identifier, 'feature' => $feature];

            if ($this->exception instanceof Throwable) {
                throw $this->exception;
            }
        }
    };
}

it('rate limits AI image generation and records successful history', function (): void {
    $rateLimiter = makeAiImageRateLimiter();
    $action = new class($rateLimiter) extends GenerateAiImageAction
    {
        protected function generateImageUrl(AiImageData $data, Provider $provider, string $model): string
        {
            return 'https://images.test/generated.png';
        }
    };

    $url = $action->handle(new AiImageData(prompt: 'A product hero image'));

    expect($url)->toBe('https://images.test/generated.png')
        ->and($rateLimiter->checks)->toBe([['identifier' => 'global', 'feature' => 'image_generation']]);

    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($history?->action)->toBe(GenerateAiImageAction::class)
        ->and($history?->failed)->toBeFalse()
        ->and($history?->output)->toBe('https://images.test/generated.png');
});

it('records failed AI image generation history', function (): void {
    $rateLimiter = makeAiImageRateLimiter();
    $action = new class($rateLimiter) extends GenerateAiImageAction
    {
        protected function generateImageUrl(AiImageData $data, Provider $provider, string $model): string
        {
            throw new RuntimeException('Image provider failed');
        }
    };

    expect(fn (): string => $action->handle(new AiImageData(prompt: 'A product hero image')))
        ->toThrow(RuntimeException::class, 'Image provider failed');

    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($history?->action)->toBe(GenerateAiImageAction::class)
        ->and($history?->failed)->toBeTrue()
        ->and($history?->error_message)->toBe('Image provider failed');
});

it('enforces AI image rate limits before calling the image provider', function (): void {
    $rateLimiter = makeAiImageRateLimiter(new RuntimeException('AI rate limit exceeded'));
    $action = new class($rateLimiter) extends GenerateAiImageAction
    {
        public bool $providerWasCalled = false;

        protected function generateImageUrl(AiImageData $data, Provider $provider, string $model): string
        {
            $this->providerWasCalled = true;

            return 'https://images.test/generated.png';
        }
    };

    expect(fn (): string => $action->handle(new AiImageData(prompt: 'A product hero image')))
        ->toThrow(RuntimeException::class, 'AI rate limit exceeded')
        ->and($action->providerWasCalled)->toBeFalse();
});
