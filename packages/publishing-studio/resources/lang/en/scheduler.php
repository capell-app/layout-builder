<?php

declare(strict_types=1);

return [
    'title' => 'Content Scheduler',
    'subheading' => 'Scheduled publishing manages release windows and review reminders.',
    'navigation' => [
        'label' => 'Content Scheduler',
        'group' => 'Workflow',
    ],
    'event_types' => [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
        'embargo' => 'Embargo',
        'review_reminder' => 'Review Reminder',
    ],
    'sources' => [
        'workspace' => 'Workspace',
        'page' => 'Page',
    ],
    'status' => [
        'page_scheduled' => 'Scheduled',
    ],
    'descriptions' => [
        'page_publish' => 'Page becomes visible.',
        'page_unpublish' => 'Page stops being visible.',
        'workspace_publish' => 'Workspace is queued for publishing.',
        'workspace_takedown_reminder' => 'Campaign takedown reminder. This does not automatically unpublish live content.',
        'workspace_embargo' => 'Workspace cannot publish before this date.',
        'workspace_review_reminder' => 'Editorial review reminder is due.',
    ],
    'table' => [
        'event_type' => 'Event',
        'source' => 'Source',
        'scheduled_for' => 'Scheduled For',
        'description' => 'Description',
    ],
    'filters' => [
        'event_type' => 'Event',
        'source' => 'Source',
    ],
    'calendar' => [
        'heading' => 'Calendar View',
        'empty' => 'No upcoming scheduled content.',
    ],
    'actions' => [
        'manage' => 'Scheduler',
    ],
    'fields' => [
        'unpublish_at' => 'Takedown Reminder At',
        'takedown_reminder_at' => 'Takedown Reminder At',
        'embargo_until' => 'Embargo Until',
        'review_reminder_at' => 'Review Reminder At',
    ],
    'notifications' => [
        'updated' => 'Scheduler updated',
    ],
];
