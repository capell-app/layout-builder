<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\Area;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateAccessGateApprovalLimitAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Area $area, ?int $approvalLimit, ?int $updatedByUserId = null): Area
    {
        if ($approvalLimit !== null && $approvalLimit < 0) {
            throw new InvalidArgumentException('Approval limit cannot be negative.');
        }

        $previousApprovalLimit = $area->approval_limit;

        $area->forceFill([
            'approval_limit' => $approvalLimit,
        ])->save();

        $this->recordEvent->handle(
            type: EventType::AreaApprovalLimitUpdated,
            area: $area,
            userId: $updatedByUserId,
            payload: [
                'previous_approval_limit' => $previousApprovalLimit,
                'approval_limit' => $approvalLimit,
                'updated_by_user_id' => $updatedByUserId,
            ],
        );

        return $area->refresh();
    }
}
