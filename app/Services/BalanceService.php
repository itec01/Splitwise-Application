<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;

class BalanceService
{
    /**
     * Return the net balance of every group member.
     * Positive = should receive; negative = owes.
     */
    public function getGroupBalances(Group $group): array
    {
        $balances = [];

        foreach ($group->member_ids ?? [] as $memberId) {
            $balances[(string) $memberId] = 0;
        }

        $groupId = (string) $group->getKey();

        $expenses = Expense::where('group_id', $groupId)->get();

        foreach ($expenses as $expense) {
            $payerId = (string) $expense->paid_by;
            $totalCents = $this->toCents($expense->amount);

            $balances[$payerId] =
                ($balances[$payerId] ?? 0) + $totalCents;

            foreach ($expense->participants ?? [] as $participant) {
                $participantId = (string) data_get($participant, 'user_id', '');
                $shareAmount = data_get($participant, 'amount');

                if ($participantId === '' || $shareAmount === null) {
                    continue;
                }

                $shareCents = $this->toCents($shareAmount);

                $balances[$participantId] =
                    ($balances[$participantId] ?? 0) - $shareCents;
            }
        }

        $settlements = Settlement::where('group_id', $groupId)->get();

        foreach ($settlements as $settlement) {
            $payerId = (string) $settlement->paid_by;
            $receiverId = (string) $settlement->paid_to;
            $amountCents = $this->toCents($settlement->amount);

            // The payer's debt decreases.
            $balances[$payerId] =
                ($balances[$payerId] ?? 0) + $amountCents;

            // The receiver's credit decreases.
            $balances[$receiverId] =
                ($balances[$receiverId] ?? 0) - $amountCents;
        }

        $result = [];

        foreach ($balances as $userId => $balanceCents) {
            $user = User::find($userId);

            $result[] = [
                'user_id' => (string) $userId,
                'name' => $user?->name,
                'balance' => round($balanceCents / 100, 2),
            ];
        }

        return $result;
    }

    /**
     * Calculate the net outstanding debt from one user to another.
     * Positive means fromUser owes toUser.
     */
    public function outstandingDebt(
        Group $group,
        string $fromUserId,
        string $toUserId
    ): float {
        return $this->calculateOutstandingDebt(
            $group,
            $fromUserId,
            $toUserId
        );
    }

    /**
     * Calculate outstanding debt while excluding one settlement.
     * Used when updating an existing settlement.
     */
    public function outstandingDebtExcludingSettlement(
        Group $group,
        string $fromUserId,
        string $toUserId,
        string $excludedSettlementId
    ): float {
        return $this->calculateOutstandingDebt(
            $group,
            $fromUserId,
            $toUserId,
            $excludedSettlementId
        );
    }

    /**
     * Calculate net debt in cents.
     */
    private function calculateOutstandingDebt(
        Group $group,
        string $fromUserId,
        string $toUserId,
        ?string $excludedSettlementId = null
    ): float {
        if ($fromUserId === $toUserId) {
            return 0.0;
        }

        $fromToCents = $this->directDebtCents(
            $group,
            $fromUserId,
            $toUserId,
            $excludedSettlementId
        );

        $toFromCents = $this->directDebtCents(
            $group,
            $toUserId,
            $fromUserId,
            $excludedSettlementId
        );

        $netDebtCents = max(0, $fromToCents) - max(0, $toFromCents);

        return max(0, $netDebtCents) / 100;
    }

    /**
     * Calculate gross expenses owed to a specific payer,
     * minus settlements already paid in that direction.
     */
    private function directDebtCents(
        Group $group,
        string $fromUserId,
        string $toUserId,
        ?string $excludedSettlementId = null
    ): int {
        if ($fromUserId === $toUserId) {
            return 0;
        }

        $groupId = (string) $group->getKey();
        $debtCents = 0;

        $expenses = Expense::where('group_id', $groupId)->get();

        foreach ($expenses as $expense) {
            if ((string) $expense->paid_by !== $toUserId) {
                continue;
            }

            foreach ($expense->participants ?? [] as $participant) {
                $participantId = (string) data_get(
                    $participant,
                    'user_id',
                    ''
                );

                if ($participantId !== $fromUserId) {
                    continue;
                }

                $shareAmount = data_get($participant, 'amount');

                if ($shareAmount !== null) {
                    $debtCents += $this->toCents($shareAmount);
                }
            }
        }

        $settlements = Settlement::where('group_id', $groupId)
            ->where('paid_by', $fromUserId)
            ->where('paid_to', $toUserId)
            ->get();

        foreach ($settlements as $settlement) {
            if (
                $excludedSettlementId !== null
                && (string) $settlement->getKey() === $excludedSettlementId
            ) {
                continue;
            }

            $debtCents -= $this->toCents($settlement->amount);
        }

        return $debtCents;
    }

    /**
     * Convert a monetary amount to integer cents.
     */
    private function toCents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
