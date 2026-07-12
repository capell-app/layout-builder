<?php

declare(strict_types=1);
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;

it('exposes layout builder contracts from the package namespace', function (): void {
    expect(interface_exists(PublicLayoutWidgetPayloadContributor::class))->toBeTrue()
        ->and(interface_exists(LayoutContentGroupContributor::class))->toBeTrue();
});

it('keeps package contributor tags stable without requiring legacy contracts', function (): void {
    expect(PublicLayoutWidgetPayloadContributor::TAG)->toBe('capell.layout_builder.public_layout_widget_payload_contributor')
        ->and(LayoutContentGroupContributor::TAG)->toBe('capell.layout_builder.content_group_contributor');
});
