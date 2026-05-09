<?php

declare(strict_types=1);

return [
    'approved' => [
        'action' => 'Claim access',
        'greeting' => 'Your access is ready',
        'lines' => [
            'approved' => 'Your request for :area has been approved.',
            'claim' => 'This link can only be used once. If it expires, request access again with the same email address.',
        ],
        'subject' => 'Your access is ready for :area',
    ],
    'expired' => [
        'greeting' => 'Access expired',
        'lines' => [
            'expired' => 'Your access to :area has expired.',
        ],
        'subject' => 'Access expired for :area',
    ],
    'request_received' => [
        'greeting' => 'Access request received',
        'lines' => [
            'next' => 'If access is approved, you will receive a secure link by email.',
            'received' => 'We have received your request for :area.',
        ],
        'subject' => 'Access request received for :area',
    ],
    'revoked' => [
        'greeting' => 'Access changed',
        'lines' => [
            'revoked' => 'Your access to :area has been revoked.',
        ],
        'subject' => 'Access changed for :area',
    ],
];
