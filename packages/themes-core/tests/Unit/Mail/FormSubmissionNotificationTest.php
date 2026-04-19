<?php

declare(strict_types=1);

use Capell\Themes\Core\Mail\FormSubmissionNotification;

test('has subject derived from form name', function (): void {
    $mail = new FormSubmissionNotification('Contact', ['name' => 'Ada']);

    expect($mail->envelope()->subject)->toBe('New Contact submission');
});

test('exposes content view with payload', function (): void {
    $mail = new FormSubmissionNotification('Contact', ['name' => 'Ada'], 'example.com');
    $content = $mail->content();

    expect($content->view)->toBe('capell::mail.form-submission');
    expect($content->with)->toMatchArray([
        'formName' => 'Contact',
        'fields' => ['name' => 'Ada'],
        'submittedFrom' => 'example.com',
    ]);
});
