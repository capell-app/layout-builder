<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Fragments;

use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Contracts\Fragments\PublicFragmentUrlResolver;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Exceptions\PublicFragmentReferenceInvalid;

final readonly class LayoutBuilderFragmentUrlResolver implements PublicFragmentUrlResolver
{
    public const string OWNER = 'layout-builder';

    public function __construct(
        private PublicFragmentReferenceCodec $codec,
    ) {}

    public function owner(): string
    {
        return self::OWNER;
    }

    public function url(PublicFragmentReferenceData $reference): string
    {
        if ($reference->owner !== self::OWNER) {
            throw new PublicFragmentReferenceInvalid;
        }

        return route(
            'capell-layout-builder.fragments.show',
            ['reference' => $this->codec->encode($reference)],
            absolute: false,
        );
    }
}
