<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GenerateAiImageAction;
use Capell\Assistant\DataObjects\AiImageData;
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\Testing\PrismFake;

it('returns an image URL from the AI provider', function (): void {
    $fake = Prism::fake([
        PrismFake::image('https://example.com/generated.jpg'),
    ]);

    $data = new AiImageData(
        prompt: 'A professional hero banner for a law firm',
        contextFields: ['title' => 'Contact Us'],
        size: '1024x1024',
    );

    $action = new GenerateAiImageAction;
    $url = $action->handle($data);

    expect($url)->toBe('https://example.com/generated.jpg');
    $fake->assertCallCount(1);
});
