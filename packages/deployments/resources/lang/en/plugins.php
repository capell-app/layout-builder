<?php

declare(strict_types=1);

return [
    'deployment_connection' => [
        'connect_bitbucket' => 'Connect Bitbucket Repository',
        'connect_github' => 'Connect GitHub Repository',
        'connect_gitlab' => 'Connect GitLab Repository',
        'connected_to' => 'Connected to :provider — :repo',
        'disconnect' => 'Disconnect',
        'disconnect_confirm' => 'Are you sure you want to disconnect this deployment repository?',
        'disconnected' => 'Deployment repository disconnected.',
        'nav_label' => 'Deployment Repository',
        'none_connected' => 'No deployment repository is connected. Choose a Git provider above to connect the repository used for plugin deployments.',
        'not_connected' => 'No deployment repository configured.',
        'oauth_connected' => ':provider connected successfully. Please select your repository.',
        'oauth_failed' => ':provider OAuth failed. Check client credentials.',
        'oauth_invalid_state' => 'OAuth session validation failed. Please start the connection again.',
        'oauth_missing_code' => 'OAuth error: missing code parameter.',
        'oauth_user_failed' => 'Could not fetch :provider user info.',
        'title' => 'Deployment Repository',
    ],
];
