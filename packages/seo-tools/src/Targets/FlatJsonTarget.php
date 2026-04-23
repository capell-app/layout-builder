<?php

declare(strict_types=1);

namespace Capell\SeoTools\Targets;

use Capell\SeoTools\Contracts\ContentTargetContract;
use Capell\SeoTools\Models\AiCreatorSession;

class FlatJsonTarget implements ContentTargetContract
{
    public function apply(array $sections, AiCreatorSession $session): void
    {
        $session->generated_output = array_merge(
            (array) ($session->generated_output ?? []),
            ['flat_json' => $sections],
        );
        $session->save();
    }

    public function handles(): string
    {
        return 'flat_json';
    }
}
