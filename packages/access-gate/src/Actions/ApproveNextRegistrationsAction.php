<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\AccessGateDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

final class ApproveNextRegistrationsAction
{
    use AsAction;

    public function __construct(
        private readonly ApproveRegistrationAction $approveRegistration,
    ) {}

    /**
     * @return Collection<int, Registration>
     */
    public function handle(Area $area, int $count, ?int $approvedByUserId = null): Collection
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Approval count must be at least one.');
        }

        return AccessGateDatabase::transaction(function () use ($area, $count, $approvedByUserId): Collection {
            $lockedArea = Area::query()
                ->whereKey($area->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $remaining = $this->remainingApprovalCapacity($lockedArea);
            $approvalCount = $remaining === null ? $count : min($count, $remaining);

            if ($approvalCount < 1) {
                return collect();
            }

            /** @var Collection<int, Registration> $registrations */
            $registrations = Registration::query()
                ->where('access_area_id', $lockedArea->getKey())
                ->where('status', RegistrationStatus::Pending)
                ->orderBy('position')
                ->orderBy('requested_at')
                ->orderBy('id')
                ->limit($approvalCount)
                ->lockForUpdate()
                ->get();

            return $registrations
                ->map(fn (Registration $registration): Registration => $this->approveRegistration->handle($registration, $approvedByUserId))
                ->values();
        });
    }

    private function remainingApprovalCapacity(Area $area): ?int
    {
        if ($area->approval_limit === null) {
            return null;
        }

        $approvedCount = Registration::query()
            ->where('access_area_id', $area->getKey())
            ->whereIn('status', [
                RegistrationStatus::Approved,
                RegistrationStatus::Claimed,
            ])
            ->count();

        return max(0, (int) $area->approval_limit - $approvedCount);
    }
}
