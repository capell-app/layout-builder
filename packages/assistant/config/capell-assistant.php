<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GeneratorPageContentAction;

return [
    'prism' => [
        'provider' => env('AI_PROVIDER', 'openai'),
        'model' => env('AI_MODEL', 'gpt-4o'),
        'max_retries' => 3,
        'retry_delay_ms' => 500,
        'max_tokens' => 4096,
        'image_provider' => env('AI_IMAGE_PROVIDER', 'openai'),
        'image_model' => env('AI_IMAGE_MODEL', 'dall-e-3'),
        'image_size' => env('AI_IMAGE_SIZE', '1024x1024'),
    ],
    'ai_creator' => [
        'enabled' => env('AI_CREATOR_ENABLED', true),
    ],
    'prompts' => [
        'title_generation' => [
            'system' => 'You are a helpful assistant that writes concise, SEO-friendly page titles.',
            'user_template' => 'Generate a compelling page title for the following content: {{content}}. Current title: {{current_title}}. Keywords: {{keywords}}. Limit to 70 characters.',
        ],
        'meta_description' => [
            'system' => 'You are a helpful assistant that writes accurate and engaging meta descriptions.',
            'user_template' => 'Write an SEO meta description (max 160 characters) for: {{content}}. Keywords: {{keywords}}.',
        ],
        'content_generation' => [
            'system' => 'You are a helpful assistant that writes engaging, accessible, and SEO-friendly HTML page content. Prefer short paragraphs, meaningful headings (h2/h3), and occasional lists. Keep tone friendly and informative.',
            'user_template' => 'Generate or refactor content. Title: {{current_title}}. Keywords: {{keywords}}. Existing content: {{content}}. Target length: {{target_length}} words. Refactor existing: {{refactor}}. Output clean HTML only (paragraphs, h2/h3 headings, lists). Include a concise call to action where appropriate. Avoid scripts, styles, iframes, and external links.',
        ],
        'ai_creator_layout' => [
            'system' => <<<'PROMPT'
You are an expert CMS content architect. Your job is to propose a structured page layout composed of named section types.

RULES:
- Propose sections as a JSON array only — no prose, no HTML, no markdown outside the JSON.
- Each section must map to one of the registered section types provided to you.
- Output ONLY original content. Never reproduce real brand copy, logos, or copyrighted material.
- All text values in the output are placeholder suggestions, clearly labelled as such.
- Image fields must be set to null — images are handled separately.
- Maximum 8 sections per page.

Respond with a JSON array of section objects in this exact shape:
[
  {
    "section_type": "<registered key>",
    "fields": {
      "<field_name>": "<placeholder text>"
    },
    "ai_metadata": {
      "ai_placeholder": true
    }
  }
]
PROMPT,
            'user_template' => <<<'PROMPT'
Page intent: {{intent}}
Tone: {{tone}}
Industry: {{industry}}
Target audience: {{target_audience}}

Available section types:
{{section_types}}

Brand voice notes: {{brand_voice_notes}}

Propose a layout. Respond with JSON only.
PROMPT,
        ],
        'ai_creator_clarify' => [
            'system' => 'You are a friendly CMS assistant helping a user build a page. Ask ONE short clarifying question to help you understand their intent better. Keep it concise. Do not ask about tone or brand — those are handled separately.',
            'user_template' => 'The user wants to create: {{intent}}. What single question would help you propose a better layout? Reply with just the question, nothing else.',
        ],
        'ai_image_generation' => [
            'system' => 'You are an expert at writing image generation prompts. Given context about a page, write a concise, vivid image generation prompt. Focus on visual composition, mood, and subject. No text in the image.',
            'user_template' => 'Generate a professional image for: {{context}}. Style: {{style}}. Size ratio: {{size}}. Respond with just the image generation prompt.',
        ],
    ],
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
    ],
    'features' => [
        'title_generation' => [
            'enabled' => true,
            'model' => 'gpt-4o',
            'handler' => 'Capell\\Admin\\Actions\\AI\\GeneratePageTitleAction',
        ],
        'meta_description' => [
            'enabled' => true,
            'model' => 'gpt-4o',
            'handler' => 'Capell\\Admin\\Actions\\AI\\GenerateMetaDescriptionAction',
        ],
        'content_generation' => [
            'enabled' => true,
            'model' => 'gpt-4o',
            'handler' => GeneratorPageContentAction::class,
        ],
        'ai_creator' => [
            'enabled' => true,
            'model' => 'gpt-4o',
            'handler' => null,
        ],
    ],
    'cache' => [
        'ttl' => 86400, // 1 day
    ],
];
