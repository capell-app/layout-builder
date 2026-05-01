<?php

declare(strict_types=1);

return [
    'title' => 'Content Scheduler',
    'subheading' => 'Plan workspace releases, page publish windows, embargoes, unpublishing, and editorial review reminders from one calendar-ready queue.',
    'navigation' => [
        'label' => 'Content Scheduler',
        'group' => 'Content',
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
        'workspace_unpublish' => 'Workspace has a campaign takedown date.',
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
        'unpublish_at' => 'Unpublish At',
        'embargo_until' => 'Embargo Until',
        'review_reminder_at' => 'Review Reminder At',
    ],
    'notifications' => [
        'updated' => 'Scheduler updated',
    ],
];
