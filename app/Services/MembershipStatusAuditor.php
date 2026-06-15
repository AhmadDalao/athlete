<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use Carbon\CarbonInterface;

class MembershipStatusAuditor
{
    /**
     * @return array{processed:int, updated:int, breakdown:array<string, int>}
     */
    public function run(): array
    {
        $processed = 0;
        $updated = 0;
        $breakdown = [];
        $today = now()->startOfDay();

        Membership::query()
            ->orderBy('id')
            ->chunkById(200, function ($memberships) use (&$processed, &$updated, &$breakdown, $today): void {
                foreach ($memberships as $membership) {
                    $nextStatus = $this->resolveStatus($membership, $today);

                    $processed++;
                    $breakdown[$nextStatus->value] = ($breakdown[$nextStatus->value] ?? 0) + 1;

                    if ($membership->status === $nextStatus) {
                        continue;
                    }

                    $membership->forceFill([
                        'status' => $nextStatus,
                    ])->save();

                    $updated++;
                }
            });

        ksort($breakdown);

        return [
            'processed' => $processed,
            'updated' => $updated,
            'breakdown' => $breakdown,
        ];
    }

    public function resolveStatus(Membership $membership, CarbonInterface $today): MembershipStatus
    {
        $effectiveEndDate = $membership->effectiveEndDate()?->copy()->startOfDay();
        $endsAt = $membership->ends_at?->copy()->startOfDay();
        $graceEndsAt = $membership->grace_ends_at?->copy()->startOfDay();
        $renewsAt = $membership->renews_at?->copy()->startOfDay();
        $startsAt = $membership->starts_at?->copy()->startOfDay();

        if ($graceEndsAt && $today->gt($graceEndsAt)) {
            return MembershipStatus::Expired;
        }

        if ($endsAt && $today->gt($endsAt) && $graceEndsAt && $today->lte($graceEndsAt)) {
            return $membership->status === MembershipStatus::PastDue
                ? MembershipStatus::PastDue
                : MembershipStatus::Grace;
        }

        if ($membership->cancelled_at && (! $effectiveEndDate || $today->lte($effectiveEndDate))) {
            return MembershipStatus::Cancelled;
        }

        if ($membership->status === MembershipStatus::PastDue && (! $effectiveEndDate || $today->lte($effectiveEndDate))) {
            return MembershipStatus::PastDue;
        }

        if (
            $membership->status === MembershipStatus::Trialing
            && $renewsAt
            && $today->lte($renewsAt)
        ) {
            return MembershipStatus::Trialing;
        }

        if ($startsAt && $today->lt($startsAt)) {
            return MembershipStatus::Trialing;
        }

        if ($effectiveEndDate && $today->gt($effectiveEndDate)) {
            return MembershipStatus::Expired;
        }

        return MembershipStatus::Active;
    }
}
