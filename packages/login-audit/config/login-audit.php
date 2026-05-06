<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Rappasoft\LaravelLoginAudit\Listeners\FailedLoginListener;
use Rappasoft\LaravelLoginAudit\Listeners\LoginListener;
use Rappasoft\LaravelLoginAudit\Listeners\LogoutListener;
use Rappasoft\LaravelLoginAudit\Listeners\OtherDeviceLogoutListener;
use Rappasoft\LaravelLoginAudit\Notifications\FailedLogin;
use Rappasoft\LaravelLoginAudit\Notifications\NewDevice;

return [
    // The database table name
    // You can change this if the database keys get too long for your driver
    'table_name' => 'login_audit',

    // The database connection where the login_audit table resides. Leave empty to use the default
    'db_connection' => null,

    // The events the package listens for to log
    'events' => [
        'login' => Login::class,
        'failed' => Failed::class,
        'logout' => Logout::class,
        'logout-other-devices' => OtherDeviceLogout::class,
    ],

    'listeners' => [
        'login' => LoginListener::class,
        'failed' => FailedLoginListener::class,
        'logout' => LogoutListener::class,
        'logout-other-devices' => OtherDeviceLogoutListener::class,
    ],

    'notifications' => [
        'new-device' => [
            // Send the NewDevice notification
            'enabled' => true,

            // Use torann/geoip to attempt to get a location
            'location' => false,

            // The Notification class to send
            'template' => NewDevice::class,
        ],
        'failed-login' => [
            // Send the FailedLogin notification
            'enabled' => false,

            // Use torann/geoip to attempt to get a location
            'location' => false,

            // The Notification class to send
            'template' => FailedLogin::class,
        ],
    ],

    // When the clean-up command is run, delete old logs greater than `purge` days
    // Don't schedule the clean-up command if you want to keep logs forever.
    'purge' => 365,

    // If you are behind a CDN proxy, set 'behind_cdn.http_header_field' to the corresponding http header field of your CDN
    // For Cloudflare: https://developers.cloudflare.com/fundamentals/get-started/reference/http-request-headers/
    //    'behind_cdn' => [
    //        'http_header_field' => 'HTTP_CF_CONNECTING_IP' // used by Cloudflare
    //    ],

    // If you are not a CDN user, use false
    'behind_cdn' => false,
];
