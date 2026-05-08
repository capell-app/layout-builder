<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Newsletter\Data\ConsentEvidenceData;
use Capell\Newsletter\Data\SubscriberData;
use Capell\Newsletter\Enums\ConfirmationMode;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Models\FormMapping;
use Capell\Newsletter\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

class SubscribeFromFormSubmissionAction
{
    use AsAction;

    public function handle(object $event): ?Subscriber
    {
        $form = $event->form ?? null;

        if (! $form instanceof Model || ! is_a($form, $this->formModelClass())) {
            return null;
        }

        $submission = $event->submission ?? null;

        $submissionPayload = $submission instanceof Model ? $submission->getAttribute('payload') : null;

        if (! $submission instanceof Model || ! is_a($submission, $this->submissionModelClass()) || ! is_object($submissionPayload)) {
            return null;
        }

        $mapping = $this->resolveMapping($form);

        if (! $mapping instanceof FormMapping) {
            return null;
        }

        $payload = $submissionPayload->values ?? null;

        if (! is_array($payload)) {
            return null;
        }

        $email = $this->payloadString($payload, $mapping->email_field);

        if ($email === null) {
            return null;
        }

        $hasSubmittedConsent = $mapping->consent_field !== null
            && $this->payloadBoolean($payload, $mapping->consent_field);
        $evidence = $hasSubmittedConsent ? $this->evidence($mapping, $submission->getAttribute('meta'), (string) $submission->getKey()) : null;
        $targetStatus = $evidence instanceof ConsentEvidenceData && ! $mapping->requires_double_opt_in
            ? SubscriberStatus::Subscribed
            : SubscriberStatus::Pending;

        $subscriber = UpsertSubscriberAction::run(new SubscriberData(
            siteId: (int) $form->getAttribute('site_id'),
            email: $email,
            status: $targetStatus,
            firstName: $this->payloadString($payload, $mapping->first_name_field),
            lastName: $this->payloadString($payload, $mapping->last_name_field),
            sourceFormId: (int) $form->getKey(),
            sourceFormHandle: (string) $form->getAttribute('handle'),
        ), $evidence);

        ApplyNewsletterTagsAction::run($subscriber, $this->resolvedTagIds($mapping, $payload));

        if ($targetStatus === SubscriberStatus::Subscribed) {
            QueueProviderSyncAction::run($subscriber);

            return $subscriber;
        }

        if ($mapping->requires_double_opt_in && $mapping->confirmation_mode === ConfirmationMode::CapellOwned) {
            RequestDoubleOptInAction::run($subscriber, $evidence);
        }

        if ($mapping->requires_double_opt_in && $mapping->confirmation_mode === ConfirmationMode::ProviderOwned) {
            QueueProviderSyncAction::run($subscriber);
        }

        return $subscriber;
    }

    private function resolveMapping(Model $form): ?FormMapping
    {
        return FormMapping::query()
            ->active()
            ->where('site_id', $form->getAttribute('site_id'))
            ->where(function (Builder $query) use ($form): void {
                $query->where('form_id', $form->getKey());

                $formHandle = $form->getAttribute('handle');

                if (is_string($formHandle) && $formHandle !== '') {
                    $query->orWhere('form_handle', $formHandle);
                }
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadString(array $payload, ?string $field): ?string
    {
        if ($field === null || $field === '') {
            return null;
        }

        $value = $payload[$field] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadBoolean(array $payload, string $field): bool
    {
        $value = $payload[$field] ?? false;

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function evidence(FormMapping $mapping, ?object $metadata, string $submissionId): ConsentEvidenceData
    {
        return new ConsentEvidenceData(
            sourceType: 'form_submission',
            sourceId: $submissionId,
            consentText: $mapping->consent_text,
            consentVersion: $mapping->consent_version,
            ipAddress: $metadata?->ipAddress,
            userAgent: $metadata?->userAgent,
            url: $metadata?->url,
            referer: $metadata?->referer,
        );
    }

    private function formModelClass(): string
    {
        return implode('\\', ['Capell', 'FormBuilder', 'Models', 'Form']);
    }

    private function submissionModelClass(): string
    {
        return implode('\\', ['Capell', 'FormBuilder', 'Models', 'Submission']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, int>
     */
    private function resolvedTagIds(FormMapping $mapping, array $payload): array
    {
        $tagIds = array_map(
            static fn (mixed $tagId): int => (int) $tagId,
            is_array($mapping->fixed_tag_ids) ? $mapping->fixed_tag_ids : [],
        );
        $fieldMappings = is_array($mapping->field_tag_mappings) ? $mapping->field_tag_mappings : [];

        foreach ($fieldMappings as $field => $valueMappings) {
            if (! is_string($field)) {
                continue;
            }

            if (! is_array($valueMappings)) {
                continue;
            }

            $submittedValue = $payload[$field] ?? null;
            $submittedValues = is_array($submittedValue) ? $submittedValue : [$submittedValue];

            foreach ($submittedValues as $submittedItem) {
                $submittedKey = is_bool($submittedItem)
                    ? ($submittedItem ? 'true' : 'false')
                    : (string) $submittedItem;
                $tagId = $valueMappings[$submittedKey] ?? $valueMappings[(string) (int) filter_var($submittedItem, FILTER_VALIDATE_BOOL)] ?? null;

                if (is_numeric($tagId)) {
                    $tagIds[] = (int) $tagId;
                }
            }
        }

        return array_values(array_unique(array_filter(
            $tagIds,
            static fn (int $tagId): bool => $tagId > 0,
        )));
    }
}
