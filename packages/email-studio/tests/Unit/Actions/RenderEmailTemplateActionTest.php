<?php

declare(strict_types=1);

use Capell\EmailStudio\Actions\RenderEmailTemplateAction;
use Capell\EmailStudio\Data\EmailContextData;
use Capell\EmailStudio\Exceptions\EmailTemplateRenderingException;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;

it('renders declared variables safely and handles missing variables by mode', function (): void {
    $template = EmailTemplate::factory()->create([
        'variables' => ['name'],
    ]);

    $variant = EmailTemplateVariant::factory()->for($template, 'template')->create([
        'subject' => 'Hello {{ name }}',
        'preview_text' => 'Preview for {{ name }}',
        'html_body' => '<p>Hello {{ name }}</p><p>{{ unsafe }}</p>',
        'text_body' => 'Hello {{ name }} {{ unsafe }}',
    ]);

    $preview = RenderEmailTemplateAction::run(
        variant: $variant,
        context: EmailContextData::from([
            'variables' => ['name' => '<Ben>'],
            'preview' => true,
        ]),
    );

    expect($preview->subject)->toBe('Hello &lt;Ben&gt;')
        ->and($preview->previewText)->toBe('Preview for &lt;Ben&gt;')
        ->and($preview->html)->toContain('<p>Hello &lt;Ben&gt;</p>')
        ->and($preview->html)->toContain('<p>{{ unsafe }}</p>')
        ->and($preview->text)->toContain('Hello <Ben> {{ unsafe }}');

    expect(fn (): mixed => RenderEmailTemplateAction::run(
        variant: $variant,
        context: EmailContextData::from([
            'variables' => ['name' => 'Ben'],
            'preview' => false,
        ]),
    ))->toThrow(EmailTemplateRenderingException::class);
});
