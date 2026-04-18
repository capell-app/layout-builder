<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('assistant.ai_creator', true);
        $this->migrator->add('assistant.ai_provider', 'openai');
        $this->migrator->add('assistant.ai_model', 'gpt-4o');
        $this->migrator->add('assistant.ai_api_key', '');
        $this->migrator->add('assistant.image_provider', 'openai');
        $this->migrator->add('assistant.image_model', 'dall-e-3');
        $this->migrator->add('assistant.image_default_size', '1024x1024');
    }

    public function down(): void
    {
        $this->migrator->delete('assistant.ai_creator');
        $this->migrator->delete('assistant.ai_provider');
        $this->migrator->delete('assistant.ai_model');
        $this->migrator->delete('assistant.ai_api_key');
        $this->migrator->delete('assistant.image_provider');
        $this->migrator->delete('assistant.image_model');
        $this->migrator->delete('assistant.image_default_size');
    }
};
