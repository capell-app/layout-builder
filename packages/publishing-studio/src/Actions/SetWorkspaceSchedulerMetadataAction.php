<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lorisleiva\Actions\Concerns\AsAction;

final class SetWorkspaceSchedulerMetadataAction
{
    use AsAction;

    /**
     * @param  array{unpublish_at?: CarbonInterface|string|null, embargo_until?: CarbonInterface|string|null, review_reminder_at?: CarbonInterface|string|null}  $metadata
     */
    public function handle(Workspace $workspace, array $metadata): Workspace
    {
        foreach (['unpublish_at', 'embargo_until', 'review_reminder_at'] as $field) {
            if (! array_key_exists($field, $metadata)) {
                continue;
            }

            $workspace->setAttribute($field, $this->parseDate($metadata[$field]));
        }

        $workspace->save();

        return $workspace->refresh();
    }

    private function parseDate(CarbonInterface|string|null $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        return CarbonImmutable::parse($value);
    }
}
