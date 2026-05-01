<?php

declare(strict_types=1);

namespace Capell\Campaigns\Listeners;

use Capell\Campaigns\Actions\RecordFormSubmissionConversionAction;

final class RecordFormSubmissionConversion
{
    public function handle(object $event): void
    {
        $form = $event->form ?? null;

        if (! is_object($form)) {
            return;
        }

        $handle = $form->handle ?? null;
        $formId = method_exists($form, 'getKey') ? (string) $form->getKey() : null;
        $target = is_string($handle) && $handle !== '' ? $handle : $formId;

        if (! is_string($target) || $target === '') {
            return;
        }

        RecordFormSubmissionConversionAction::run($target);
    }
}
