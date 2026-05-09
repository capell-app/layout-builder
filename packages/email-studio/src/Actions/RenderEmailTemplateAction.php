<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Actions;

use Capell\EmailStudio\Data\EmailContextData;
use Capell\EmailStudio\Data\RenderedEmailData;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Capell\EmailStudio\Support\EmailVariableRenderer;
use Lorisleiva\Actions\Concerns\AsAction;

class RenderEmailTemplateAction
{
    use AsAction;

    public function handle(EmailTemplateVariant $variant, EmailContextData $context): RenderedEmailData
    {
        $variant->loadMissing('template');

        $declaredVariables = $variant->template?->variables;
        $declaredVariables = is_array($declaredVariables) ? array_values($declaredVariables) : [];
        $renderer = resolve(EmailVariableRenderer::class);

        return new RenderedEmailData(
            subject: $renderer->renderHtml($variant->subject, $context->variables, $declaredVariables, $context->preview),
            previewText: $renderer->renderEscapedText($variant->preview_text, $context->variables, $declaredVariables, $context->preview),
            html: $renderer->renderHtml($variant->html_body, $context->variables, $declaredVariables, $context->preview),
            text: $renderer->renderText($variant->text_body, $context->variables, $declaredVariables, $context->preview),
        );
    }
}
