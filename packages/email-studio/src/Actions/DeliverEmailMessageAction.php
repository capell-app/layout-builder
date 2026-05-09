<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Actions;

use Capell\EmailStudio\Enums\EmailMessageStatus;
use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Support\EmailProviderRegistry;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class DeliverEmailMessageAction
{
    use AsAction;

    public function handle(EmailMessage|int $message): EmailMessage
    {
        $emailMessage = $message instanceof EmailMessage ? $message : EmailMessage::query()->findOrFail($message);
        $emailMessage->loadMissing(['profile', 'recipients']);

        $this->markNewSuppressions($emailMessage);

        if (! $emailMessage->recipients()->where('status', EmailRecipientStatus::Queued->value)->exists()) {
            $emailMessage->update([
                'status' => EmailMessageStatus::Failed,
                'failed_at' => now()->toImmutable(),
                'failure_reason' => 'All recipients are suppressed.',
            ]);

            return $emailMessage->fresh(['profile', 'recipients']) ?? $emailMessage;
        }

        try {
            $providerResult = resolve(EmailProviderRegistry::class)
                ->adapter($emailMessage->profile->provider)
                ->send($emailMessage->fresh(['profile', 'recipients']) ?? $emailMessage);
        } catch (Throwable $exception) {
            $this->markProviderFailure($emailMessage, $exception->getMessage());

            return $emailMessage->fresh(['profile', 'recipients']) ?? $emailMessage;
        }

        if (! $providerResult->successful) {
            $this->markProviderFailure($emailMessage, $providerResult->failureReason);

            return $emailMessage->fresh(['profile', 'recipients']) ?? $emailMessage;
        }

        foreach ($emailMessage->recipients()->where('status', EmailRecipientStatus::Queued->value)->get() as $recipient) {
            $recipientKey = (int) $recipient->getKey();
            $failureReason = $providerResult->failedRecipientReasons[$recipientKey] ?? null;

            if ($failureReason !== null) {
                $recipient->update([
                    'status' => EmailRecipientStatus::Failed,
                    'failure_reason' => $failureReason,
                ]);

                continue;
            }

            $recipient->update([
                'status' => EmailRecipientStatus::Sent,
                'provider_message_id' => $providerResult->recipientProviderMessageIds[$recipientKey] ?? null,
                'sent_at' => now()->toImmutable(),
            ]);
        }

        $emailMessage->update($this->messageStatusAttributes($emailMessage, $providerResult->failureReason));

        return $emailMessage->fresh(['profile', 'recipients']) ?? $emailMessage;
    }

    private function markProviderFailure(EmailMessage $message, ?string $failureReason): void
    {
        $resolvedFailureReason = $failureReason ?? 'Provider failed to send the message.';

        foreach ($message->recipients()->where('status', EmailRecipientStatus::Queued->value)->get() as $recipient) {
            $recipient->update([
                'status' => EmailRecipientStatus::Failed,
                'failure_reason' => $resolvedFailureReason,
            ]);
        }

        $message->update([
            'status' => EmailMessageStatus::Failed,
            'failed_at' => now()->toImmutable(),
            'failure_reason' => $resolvedFailureReason,
        ]);
    }

    private function markNewSuppressions(EmailMessage $message): void
    {
        foreach ($message->recipients()->where('status', EmailRecipientStatus::Queued->value)->get() as $recipient) {
            if ((new CheckEmailSuppressionAction)->handle($recipient->email, $message->site_scope_key) === false) {
                continue;
            }

            $recipient->update([
                'status' => EmailRecipientStatus::Suppressed,
                'suppressed_at' => now()->toImmutable(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function messageStatusAttributes(EmailMessage $message, ?string $failureReason): array
    {
        $recipientStatuses = $message->recipients()->pluck('status');
        $sentCount = $recipientStatuses->filter(
            fn (EmailRecipientStatus|string $status): bool => ($status instanceof EmailRecipientStatus ? $status : EmailRecipientStatus::from($status)) === EmailRecipientStatus::Sent,
        )->count();

        $failedCount = $recipientStatuses->count() - $sentCount;

        if ($sentCount === 0) {
            return [
                'status' => EmailMessageStatus::Failed,
                'failed_at' => now()->toImmutable(),
                'failure_reason' => $failureReason,
            ];
        }

        return [
            'status' => $failedCount > 0 ? EmailMessageStatus::PartiallyFailed : EmailMessageStatus::Sent,
            'sent_at' => now()->toImmutable(),
            'failure_reason' => $failureReason,
        ];
    }
}
