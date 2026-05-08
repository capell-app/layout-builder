<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Events\Enums\EventNotificationTypeEnum;
use Capell\Events\Models\EventNotificationLog;
use Capell\Events\Models\EventRegistration;
use Capell\Events\Notifications\EventRegistrationNotification;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

/**
 * @method static EventNotificationLog run(EventRegistration $registration, EventNotificationTypeEnum $type)
 */
class SendEventNotificationAction
{
    use AsAction;

    public function handle(EventRegistration $registration, EventNotificationTypeEnum $type): EventNotificationLog
    {
        /** @var EventNotificationLog $log */
        $log = EventNotificationLog::query()->firstOrCreate([
            'event_occurrence_id' => $registration->event_occurrence_id,
            'event_registration_id' => $registration->getKey(),
            'type' => $type,
            'recipient_email' => $registration->email,
        ], [
            'status' => 'queued',
            'scheduled_for' => now(),
        ]);

        if ($log->wasRecentlyCreated === false && in_array($log->status, ['queued', 'sent'], true)) {
            return $log;
        }

        try {
            Notification::route('mail', $registration->email)
                ->notify(new EventRegistrationNotification($registration, $type));

            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'error' => null,
            ])->save();
        } catch (Throwable $throwable) {
            $log->forceFill([
                'status' => 'failed',
                'error' => $throwable->getMessage(),
            ])->save();
        }

        return $log->refresh();
    }
}
