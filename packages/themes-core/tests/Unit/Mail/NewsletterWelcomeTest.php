<?php

declare(strict_types=1);

use Capell\Themes\Core\Mail\NewsletterWelcome;

test('has welcome subject', function (): void {
    $mail = new NewsletterWelcome('Capell');

    expect($mail->envelope()->subject)->toBe('Welcome to Capell');
});

test('passes data to the view', function (): void {
    $mail = new NewsletterWelcome('Capell', 'Ada', 'https://example.com/unsubscribe');
    $content = $mail->content();

    expect($content->view)->toBe('capell::mail.newsletter-welcome');
    expect($content->with)->toMatchArray([
        'siteName' => 'Capell',
        'subscriberName' => 'Ada',
        'unsubscribeUrl' => 'https://example.com/unsubscribe',
    ]);
});
