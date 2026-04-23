<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\GenerateAiImageAction;
use Capell\SeoTools\DataObjects\AiImageData;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\ImageResponseFake;
use Prism\Prism\ValueObjects\GeneratedImage;

it('returns an image URL from the AI provider', function (): void {
    $fake = Prism::fake([
        ImageResponseFake::make()->withImages([new GeneratedImage(url: 'https://example.com/generated.jpg')]),
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
