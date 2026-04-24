<?php

declare(strict_types=1);

use Capell\Themes\Core\Analytics\UtmCollector;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

function makeSession(): Store
{
    return new Store('capell_test', new ArraySessionHandler(60));
}

test('captures utm parameters from request query', function (): void {
    $session = makeSession();
    $collector = new UtmCollector($session);

    $request = Request::create('/?utm_source=newsletter&utm_medium=email&utm_campaign=spring');
    $captured = $collector->capture($request);

    expect($captured)->toMatchArray([
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
        'utm_campaign' => 'spring',
    ]);
});

test('preserves existing utm values when not re-sent', function (): void {
    $session = makeSession();
    $collector = new UtmCollector($session);

    $collector->capture(Request::create('/?utm_source=initial'));
    $collector->capture(Request::create('/?utm_campaign=later'));

    expect($collector->get('utm_source'))->toBe('initial');
    expect($collector->get('utm_campaign'))->toBe('later');
});

test('emits a javascript snippet for the window', function (): void {
    $session = makeSession();
    $collector = new UtmCollector($session);
    $collector->capture(Request::create('/?utm_source=x'));

    expect($collector->toJavaScript('FOO'))
        ->toContain('window.FOO = ')
        ->toContain('"utm_source":"x"');
});
