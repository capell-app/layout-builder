<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Actions;

use Capell\EmailStudio\Data\EmailAddressData;
use Capell\EmailStudio\Data\EmailContextData;
use Capell\EmailStudio\Data\EmailHeaderData;
use Capell\EmailStudio\Data\SendEmailData;
use Capell\EmailStudio\Enums\EmailMessageStatus;
use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Capell\EmailStudio\Enums\EmailTemplateStatus;
use Capell\EmailStudio\Exceptions\EmailStudioSendingException;
use Capell\EmailStudio\Jobs\SendEmailJob;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Support\EmailAddressNormalizer;
use Capell\EmailStudio\Support\EmailProfileResolver;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class SendEmailAction
{
    use AsAction;

    public function handle(SendEmailData $data): EmailMessage
    {
        if ($this->recipientRows($data)->isEmpty()) {
            throw EmailStudioSendingException::noRecipients($data->templateKey);
        }

        $profile = resolve(EmailProfileResolver::class)->resolve($data->siteScopeKey, $data->emailProfileId)
            ?? throw EmailStudioSendingException::profileNotFound($data->siteScopeKey);

        $template = $this->resolveTemplate($data);
        $variant = ResolveEmailTemplateVariantAction::run($template, $data->siteScopeKey, $data->locale ?? app()->getLocale())
            ?? throw EmailStudioSendingException::variantNotFound($data->templateKey, $data->siteScopeKey);

        $renderedEmail = RenderEmailTemplateAction::run(
            variant: $variant,
            context: new EmailContextData(variables: $data->variables),
        );

        /** @var EmailMessage $message */
        $message = EmailMessage::query()->create([
            'site_id' => $data->siteId,
            'site_scope_key' => $data->siteScopeKey,
            'email_profile_id' => $profile->getKey(),
            'email_template_id' => $template->getKey(),
            'email_template_variant_id' => $variant->getKey(),
            'status' => $data->queue ? EmailMessageStatus::Queued : EmailMessageStatus::Requested,
            'subject' => $renderedEmail->subject,
            'preview_text' => $renderedEmail->previewText,
            'rendered_html' => $renderedEmail->html,
            'rendered_text' => $renderedEmail->text,
            'context_snapshot' => $data->variables,
            'headers' => $this->headersToArray($data),
            'triggered_by_type' => $data->triggeredByType,
            'triggered_by_id' => $data->triggeredById,
            'queued_at' => $data->queue ? now()->toImmutable() : null,
        ]);

        $this->createRecipients($message, $data);

        if ($data->queue && $message->recipients()->where('status', EmailRecipientStatus::Queued->value)->exists()) {
            SendEmailJob::dispatch((int) $message->getKey())->onQueue((string) config('capell-email-studio.queue'));
        }

        if (! $data->queue) {
            return DeliverEmailMessageAction::run($message);
        }

        return $message->fresh(['profile', 'template', 'templateVariant', 'recipients']) ?? $message;
    }

    private function resolveTemplate(SendEmailData $data): EmailTemplate
    {
        $template = EmailTemplate::query()
            ->where('key', $data->templateKey)
            ->where('status', EmailTemplateStatus::Approved)
            ->whereIn('site_scope_key', [$data->siteScopeKey, 'global'])
            ->orderByRaw('case when site_scope_key = ? then 0 else 1 end', [$data->siteScopeKey])
            ->first();

        if (! $template instanceof EmailTemplate) {
            throw EmailStudioSendingException::templateNotFound($data->templateKey, $data->siteScopeKey);
        }

        return $template;
    }

    private function createRecipients(EmailMessage $message, SendEmailData $data): void
    {
        $normalizer = resolve(EmailAddressNormalizer::class);

        foreach ($this->recipientRows($data) as $recipientRow) {
            $normalizedEmail = $normalizer->normalize($recipientRow['address']->email);
            $suppressed = (new CheckEmailSuppressionAction)->handle($recipientRow['address']->email, $data->siteScopeKey);

            $message->recipients()->create([
                'site_id' => $data->siteId,
                'site_scope_key' => $data->siteScopeKey,
                'type' => $recipientRow['type'],
                'email' => $recipientRow['address']->email,
                'normalized_email' => $normalizedEmail,
                'email_hash' => $normalizer->hash($recipientRow['address']->email),
                'name' => $recipientRow['address']->name,
                'status' => $suppressed ? EmailRecipientStatus::Suppressed : EmailRecipientStatus::Queued,
                'suppressed_at' => $suppressed ? now()->toImmutable() : null,
            ]);
        }
    }

    /**
     * @return Collection<int, array{type: string, address: EmailAddressData}>
     */
    private function recipientRows(SendEmailData $data): Collection
    {
        return collect([
            'to' => $data->to,
            'cc' => $data->cc,
            'bcc' => $data->bcc,
        ])->flatMap(fn (mixed $addresses, string $type): array => collect($addresses)
            ->map(fn (mixed $address): array => [
                'type' => $type,
                'address' => $address instanceof EmailAddressData ? $address : EmailAddressData::from($address),
            ])
            ->all())
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function headersToArray(SendEmailData $data): array
    {
        return collect($data->headers)
            ->mapWithKeys(function (mixed $header): array {
                $headerData = $header instanceof EmailHeaderData ? $header : EmailHeaderData::from($header);

                return [$headerData->name => $headerData->value];
            })
            ->all();
    }
}
