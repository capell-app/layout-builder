<?php

declare(strict_types=1);

return [
    'actions' => [
        'manage' => 'Scheduler',
    ],
    'calendar' => [
        'empty' => 'No upcoming scheduled content.',
        'heading' => 'Calendar View',
    ],
    'descriptions' => [
        'page_publish' => 'Page becomes visible.',
        'page_unpublish' => 'Page stops being visible.',
        'workspace_embargo' => 'Workspace is held until the embargo lifts.',
        'workspace_publish' => 'Workspace is scheduled for publication.',
        'workspace_review_reminder' => 'Workspace review reminder is due.',
        'workspace_takedown_reminder' => 'Workspace takedown reminder; this does not automatically unpublish content.',
    ],
    'event_types' => [
        'embargo' => 'Embargo',
        'publish' => 'Publish',
        'review_reminder' => 'Review Reminder',
        'unpublish' => 'Unpublish',
    ],
    'fields' => [
        'embargo_until' => 'Embargo Until',
        'review_reminder_at' => 'Review Reminder At',
        'takedown_reminder_at' => 'Takedown Reminder At',
    ],
    'filters' => [
        'event_type' => 'Event',
        'source' => 'Source',
    ],
    'navigation' => [
        'label' => 'Content Scheduler',
    ],
    'notifications' => [
        'updated' => 'Scheduler updated',
    ],
    'sources' => [
        'page' => 'Page',
        'workspace' => 'Workspace',
    ],
    'status' => [
        'page_scheduled' => 'Scheduled',
    ],
    'subheading' => 'Scheduled publishing manages release windows and review reminders.',
    'table' => [
        'description' => 'Description',
        'event_type' => 'Event',
        'scheduled_for' => 'Scheduled For',
        'source' => 'Source',
    ],
    'title' => 'Content Scheduler',
];
