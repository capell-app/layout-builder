<?php

declare(strict_types=1);

namespace Capell\Analytics\Http\Controllers;

use Capell\Analytics\Actions\RecordAnalyticsEventsAction;
use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AnalyticsBeaconController
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'visit_id' => ['nullable', 'string', 'max:80'],
            'events' => ['required', 'array', 'max:25'],
            'events.*.type' => ['required', Rule::enum(AnalyticsEventType::class)],
            'events.*.url' => ['required', 'url', 'max:512', $this->pathMaxRule()],
            'events.*.title' => ['nullable', 'string', 'max:255'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'events.*.event_name' => ['nullable', 'string', 'max:100'],
            'events.*.label' => ['nullable', 'string', 'max:255'],
            'events.*.location' => ['nullable', 'string', 'max:255'],
            'events.*.target_selector' => ['nullable', 'string', 'max:500'],
            'events.*.viewport_x' => ['nullable', 'integer'],
            'events.*.viewport_y' => ['nullable', 'integer'],
            'events.*.document_x' => ['nullable', 'integer'],
            'events.*.document_y' => ['nullable', 'integer'],
            'events.*.metadata' => ['nullable', 'array:nearest_landmark'],
            'events.*.metadata.nearest_landmark' => ['nullable', 'string', 'max:255'],
        ]);

        $visitUuid = isset($validated['visit_id']) && is_string($validated['visit_id'])
            ? $validated['visit_id']
            : null;

        /** @var list<array<string, mixed>> $events */
        $events = $validated['events'];

        $eventPayloads = [];

        foreach ($events as $event) {
            $eventData = AnalyticsEventData::from($event);
            $occurredAt = isset($event['occurred_at']) && is_string($event['occurred_at'])
                ? $event['occurred_at']
                : null;

            $eventPayloads[] = [
                'data' => $eventData,
                'occurred_at' => $occurredAt,
            ];
        }

        RecordAnalyticsEventsAction::run($visitUuid, $eventPayloads);

        return response()->noContent();
    }

    private function pathMaxRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            $path = parse_url($value, PHP_URL_PATH);

            if (is_string($path) && mb_strlen($path) > 512) {
                $fail(sprintf('The %s path must not be greater than 512 characters.', $attribute));
            }
        };
    }
}
