<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\DataObjects\AiImageData;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Support\AiRateLimiter;
use Lorisleiva\Actions\Concerns\AsAction;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

class GenerateAiImageAction
{
    use AsAction;

    public function __construct(private readonly AiRateLimiter $rateLimiter) {}

    public function handle(AiImageData $data): string
    {
        $startedAt = microtime(true);
        $providerName = $data->provider ?? config('capell-seo-tools.prism.image_provider', 'openai');
        $model = $data->model ?? config('capell-seo-tools.prism.image_model', 'dall-e-3');

        try {
            $this->rateLimiter->checkLimit('global', 'image_generation');

            $provider = $this->resolveProvider($providerName);
            $url = $this->generateImageUrl($data, $provider, $model);

            $this->recordHistory($data, $providerName, $model, $url, microtime(true) - $startedAt);

            return $url;
        } catch (Throwable $throwable) {
            $this->recordHistory($data, $providerName, $model, null, microtime(true) - $startedAt, $throwable);

            throw $throwable;
        }
    }

    protected function generateImageUrl(AiImageData $data, Provider $provider, string $model): string
    {
        $response = Prism::image()
            ->using($provider, $model)
            ->withPrompt($data->prompt)
            ->generate();

        return $response->images[0]->url() ?? $response->images[0]->base64 ?? '';
    }

    protected function resolveProvider(string $name): Provider
    {
        return match (strtolower($name)) {
            'anthropic' => Provider::Anthropic,
            'gemini', 'google' => Provider::Gemini,
            default => Provider::OpenAI,
        };
    }

    private function recordHistory(
        AiImageData $data,
        string $providerName,
        string $model,
        ?string $output,
        float $duration,
        ?Throwable $throwable = null,
    ): void {
        AIGenerationHistory::query()->create([
            'action' => self::class,
            'model' => $model,
            'input' => $data->prompt,
            'output' => $output,
            'duration' => $duration,
            'failed' => $throwable instanceof Throwable,
            'error_message' => $throwable?->getMessage(),
            'metadata' => [
                'provider' => $providerName,
                'size' => $data->size,
                'context_fields' => $data->contextFields,
            ],
        ]);
    }
}
