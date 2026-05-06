<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'theme_studio.activeTheme' => 'corporate',
            'theme_studio.activePreset' => 'boardroom',
            'theme_studio.draftTheme' => null,
            'theme_studio.draftPreset' => null,
            'theme_studio.draftWorkspaceId' => null,
            'theme_studio.brandProfile' => [
                'primaryColor' => '#1a2d6d',
                'accentColor' => '#f59e0b',
                'neutralColor' => '#111827',
                'headingFont' => 'inter',
                'bodyFont' => 'inter',
                'spacing' => 'balanced',
                'alignment' => 'left',
                'cardStyle' => 'subtle',
                'navigationStyle' => 'standard',
                'layoutPresentation' => 'structured',
                'motionIntensity' => 'subtle',
                'mediaTreatment' => 'natural',
            ],
            'theme_studio.themeOverrides' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migration->exists($key)) {
                $this->migration->add($key, $value);
            }
        }
    }
};
