<?php

declare(strict_types=1);

namespace Capell\PublicActions\Listeners;

use Capell\PublicActions\Actions\SubmitPublicActionAction;
use Illuminate\Support\Arr;

final class SubmitPublicActionFromFormSubmission
{
    public function __construct(
        private readonly SubmitPublicActionAction $submitPublicAction,
    ) {}

    public function handle(object $event): void
    {
        $form = $event->form ?? null;
        $actionKey = $this->actionKey($form);

        if ($actionKey === null) {
            return;
        }

        $payload = $this->payload($event);

        $this->submitPublicAction->handle($actionKey, [
            ...$payload,
            'source_type' => 'form_builder',
            'source_id' => (string) ($form?->getKey() ?? ''),
        ]);
    }

    private function actionKey(mixed $form): ?string
    {
        $mappings = config('capell-public-actions.form_builder.mappings', []);

        if (! is_array($mappings) || ! is_object($form)) {
            return null;
        }

        $candidates = array_filter([
            'id:' . ($form->getKey() ?? ''),
            (string) ($form->handle ?? ''),
        ], static fn (string $candidate): bool => $candidate !== '' && $candidate !== 'id:');

        foreach ($candidates as $candidate) {
            $actionKey = $mappings[$candidate] ?? null;

            if (is_string($actionKey) && $actionKey !== '') {
                return $actionKey;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(object $event): array
    {
        $submission = $event->submission ?? null;
        $payload = data_get($submission, 'payload.values', []);

        if (! is_array($payload) || $payload === []) {
            $payload = data_get($event, 'payload', []);
        }

        if (! is_array($payload)) {
            $payload = [];
        }

        $metadata = array_filter([
            'url' => data_get($submission, 'meta.url', data_get($event, 'metadata.url')),
            'referer' => data_get($submission, 'meta.referer', data_get($event, 'metadata.referer')),
            'user_agent' => data_get($submission, 'meta.userAgent', data_get($event, 'metadata.userAgent')),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            ...$payload,
            'metadata' => Arr::wrap($payload['metadata'] ?? []) + $metadata,
        ];
    }
}
