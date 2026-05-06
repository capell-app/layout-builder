<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Targets;

use Capell\SeoSuite\Contracts\ContentTargetContract;
use Capell\SeoSuite\Models\AiCreatorSession;

class FlatJsonTarget implements ContentTargetContract
{
    public function apply(array $sections, AiCreatorSession $session): void
    {
        $session->generated_output = array_merge(
            $session->generated_output ?? [],
            ['flat_json' => $sections],
        );
        $session->save();
    }

    public function handles(): string
    {
        return 'flat_json';
    }
}
