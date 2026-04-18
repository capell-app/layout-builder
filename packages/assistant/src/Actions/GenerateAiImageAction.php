<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\DataObjects\AiImageData;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Prism;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateAiImageAction
{
    use AsAction;

    public function handle(AiImageData $data): string
    {
        $providerName = $data->provider ?? config('capell-assistant.prism.image_provider', 'openai');
        $model = $data->model ?? config('capell-assistant.prism.image_model', 'dall-e-3');

        $provider = $this->resolveProvider((string) $providerName);

        $response = Prism::image()
            ->using($provider, (string) $model)
            ->withPrompt($data->prompt)
            ->generate();

        return $response->images[0]->url ?? $response->images[0]->base64 ?? '';
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
