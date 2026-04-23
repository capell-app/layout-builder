<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\DataObjects\AiImageData;
use Lorisleiva\Actions\Concerns\AsAction;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class GenerateAiImageAction
{
    use AsAction;

    public function handle(AiImageData $data): string
    {
        $providerName = $data->provider ?? config('capell-seo-tools.prism.image_provider', 'openai');
        $model = $data->model ?? config('capell-seo-tools.prism.image_model', 'dall-e-3');

        $provider = $this->resolveProvider($providerName);

        $response = Prism::image()
            ->using($provider, $model)
            ->withPrompt($data->prompt)
            ->generate();

        return $response->images[0]->url() ?? $response->images[0]->base64 ?? '';
    }

    private function resolveProvider(string $name): Provider
    {
        return match (strtolower($name)) {
            'anthropic' => Provider::Anthropic,
            'gemini', 'google' => Provider::Gemini,
            default => Provider::OpenAI,
        };
    }
}
