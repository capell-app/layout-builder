<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migration->exists('ai-orchestrator.ai_creator')) {
            $this->migration->add('ai-orchestrator.ai_creator', true);
        }

        if (! $this->migration->exists('ai-orchestrator.ai_provider')) {
            $this->migration->add('ai-orchestrator.ai_provider', 'openai');
        }

        if (! $this->migration->exists('ai-orchestrator.ai_model')) {
            $this->migration->add('ai-orchestrator.ai_model', 'gpt-4o');
        }

        if (! $this->migration->exists('ai-orchestrator.ai_api_key')) {
            $this->migration->add('ai-orchestrator.ai_api_key', '');
        }

        if (! $this->migration->exists('ai-orchestrator.image_provider')) {
            $this->migration->add('ai-orchestrator.image_provider', 'openai');
        }

        if (! $this->migration->exists('ai-orchestrator.image_model')) {
            $this->migration->add('ai-orchestrator.image_model', 'dall-e-3');
        }

        if (! $this->migration->exists('ai-orchestrator.image_default_size')) {
            $this->migration->add('ai-orchestrator.image_default_size', '1024x1024');
        }
    }

    public function down(): void
    {
        $this->migration->delete('ai-orchestrator.ai_creator');
        $this->migration->delete('ai-orchestrator.ai_provider');
        $this->migration->delete('ai-orchestrator.ai_model');
        $this->migration->delete('ai-orchestrator.ai_api_key');
        $this->migration->delete('ai-orchestrator.image_provider');
        $this->migration->delete('ai-orchestrator.image_model');
        $this->migration->delete('ai-orchestrator.image_default_size');
    }
};
