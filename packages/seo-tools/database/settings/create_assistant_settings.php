<?php

declare(strict_types=1);
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('assistant.page_content_generator')) {
            $this->migrator->add('assistant.page_content_generator', true);
        }

        if (! $this->migrator->exists('assistant.page_title_suggestions')) {
            $this->migrator->add('assistant.page_title_suggestions', true);
        }

        if (! $this->migrator->exists('assistant.meta_description_suggestions')) {
            $this->migrator->add('assistant.meta_description_suggestions', true);
        }

        if (! $this->migrator->exists('assistant.prompts')) {
            $this->migrator->add('assistant.prompts', [
                'title_generation' => true,
                'rate_limiting_requests_per_minute' => 60,
                'title_generation_system' => 'You are a helpful assistant that writes concise, SEO-friendly page titles.',
                'title_generation_user_template' => 'Generate a compelling page title for the following content: {{content}}. Current title: {{current_title}}. Keywords: {{keywords}}. Limit to 70 characters.',
                'meta_description' => true,
                'meta_description_system' => 'You are a helpful assistant that writes accurate and engaging meta descriptions.',
                'meta_description_user_template' => 'Write an SEO meta description (max 160 characters) for: {{content}}. Keywords: {{keywords}}.',
                'content_generation' => true,
                'content_generation_system' => 'You are a helpful assistant that writes engaging, accessible, and SEO-friendly HTML page content. Prefer short paragraphs, meaningful headings (h2/h3), and occasional lists. Keep tone friendly and informative.',
                'content_generation_user_template' => 'Generate or refactor content. Title: {{current_title}}. Keywords: {{keywords}}. Existing content: {{content}}. Target length: {{target_length}} words. Refactor existing: {{refactor}}. Output clean HTML only (paragraphs, h2/h3 headings, lists). Include a concise call to action where appropriate. Avoid scripts, styles, iframes, and external links.',
            ]);
        }
    }
};
