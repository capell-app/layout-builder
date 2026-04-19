<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class TeamGridWidget extends AbstractCorporateWidget
{
    public string $name = 'Team Grid';

    public string $description = 'Grid of team members with photos, roles and bios.';

    public string $view = 'corporate::components.team-grid';

    public string $icon = 'heroicon-o-user-group';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Meet the team'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'The people building and supporting your site.'],
        ['name' => 'members', 'label' => 'Team Members', 'type' => 'repeater', 'default' => [
            ['name' => 'Alex Morgan', 'role' => 'CEO', 'bio' => 'Twenty years running services businesses.', 'photo' => null],
            ['name' => 'Jamie Chen', 'role' => 'CTO', 'bio' => 'Platform, infra and developer experience.', 'photo' => null],
            ['name' => 'Priya Patel', 'role' => 'Head of Design', 'bio' => 'Typography, systems and motion.', 'photo' => null],
        ]],
    ];
}
