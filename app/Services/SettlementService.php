<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function __construct(
        protected GroupService $groupService,
        protected BalanceService $balanceService
    ) {}

    /**
     * Create a settlement from the authenticated user to another member.
     */
    public function createSettlement(
        Group $group,
        array $data,
        User $payer
    ): Settlement {

        $payerId = (string) $payer->getKey();
        $receiverId = (string) ($data['paid_to'] ?? '');

        // // If paid_by is supplied, it must match the authenticated user.
        // if (
        //     isset($data['paid_by'])
        //     && (string) $data['paid_by'] !== $payerId
        // ) {
        //     throw ValidationException::withMessages([
        //         'paid_by' => [
        //             'The payer must be the authenticated user.',
        //         ],
        //     ]);
        // }

        // Prevent paying yourself.
        if ($payerId === $receiverId) {
            throw ValidationException::withMessages([
                'paid_to' => [
                    'Payer and receiver cannot be the same user.',
                ],
            ]);
        }

        $memberIds = collect($group->member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! in_array($receiverId, $memberIds, true)) {
            throw ValidationException::withMessages([
                'paid_to' => [
                    'The receiver must be a member of this group.',
                ],
            ]);
        }

        if (! in_array($payerId, $memberIds, true)) {
            throw ValidationException::withMessages([
                'paid_by' => [
                    'The payer must be a member of this group.',
                ],
            ]);
        }

        if (! User::find($receiverId)) {
            throw ValidationException::withMessages([
                'paid_to' => [
                    'The selected receiver does not exist.',
                ],
            ]);
        }

        $amount = $this->validatedAmount($data['amount'] ?? null);

        $outstandingDebt = $this->balanceService->outstandingDebt(
            $group,
            $payerId,
            $receiverId
        );

        if ($amount > $outstandingDebt) {
            throw ValidationException::withMessages([
                'amount' => [
                    'The settlement amount cannot exceed your outstanding debt of '
                    .number_format($outstandingDebt, 2, '.', '').'.',
                ],
            ]);
        }

        $settlement = Settlement::create([
            'group_id' => (string) $group->getKey(),
            'paid_by' => $payerId,
            'paid_to' => $receiverId,
            'amount' => $amount,
            'note' => $data['note'] ?? null,
        ]);

        Log::info('Settlement created', [
            'settlement_id' => (string) $settlement->getKey(),
            'group_id' => (string) $group->getKey(),
            'paid_by' => $payerId,
            'paid_to' => $receiverId,
            'amount' => $amount,
        ]);

        return $settlement;
    }

    /**
     * Get paginated settlements for a group.
     */
    public function getGroupSettlements(
        Group $group,
        User $user,
        int $perPage = 15
    ): LengthAwarePaginator {

        return Settlement::where(
            'group_id',
            (string) $group->getKey()
        )
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * Update a settlement. Only its original payer may update it.
     */
    public function updateSettlement(
        Group $group,
        Settlement $settlement,
        array $data,
        User $user
    ): Settlement {

        $payerId = (string) $settlement->paid_by;
        $receiverId = (string) $settlement->paid_to;

        // Reject inconsistent legacy data as well.
        if ($payerId === $receiverId) {
            throw ValidationException::withMessages([
                'settlement' => [
                    'Payer and receiver cannot be the same user.',
                ],
            ]);
        }

        $newAmount = array_key_exists('amount', $data)
            ? $this->validatedAmount($data['amount'])
            : (float) $settlement->amount;

        $availableDebt = $this->balanceService
            ->outstandingDebtExcludingSettlement(
                $group,
                $payerId,
                $receiverId,
                (string) $settlement->getKey()
            );

        if ($newAmount > $availableDebt) {
            throw ValidationException::withMessages([
                'amount' => [
                    'The amount cannot exceed the available debt of '
                    .number_format($availableDebt, 2, '.', '').'.',
                ],
            ]);
        }

        $settlement->amount = $newAmount;

        if (array_key_exists('note', $data)) {
            $settlement->note = $data['note'];
        }

        $settlement->save();

        Log::info('Settlement updated', [
            'settlement_id' => (string) $settlement->getKey(),
            'updated_by' => (string) $user->getKey(),
            'amount' => $newAmount,
        ]);

        return $settlement;
    }

    /**
     * Delete a settlement. Only its original payer may delete it.
     */
    public function deleteSettlement(
        Group $group,
        Settlement $settlement,
        User $user
    ): void {

        Log::info('Settlement deleted', [
            'settlement_id' => (string) $settlement->getKey(),
            'deleted_by' => (string) $user->getKey(),
            'amount' => (float) $settlement->amount,
        ]);

        $settlement->delete();
    }

    /**
     * Ensure the settlement belongs to the requested group.
     *
     * @deprecated Authorized by middleware
     */
    private function ensureSettlementBelongsToGroup(
        Group $group,
        Settlement $settlement
    ): void {
        // Moved to EnsureSettlementAccess middleware
    }

    /**
     * Validate and normalize a positive monetary amount.
     */
    private function validatedAmount(mixed $amount): float
    {
        if (
            ! is_numeric($amount)
            || ! is_finite((float) $amount)
            || (float) $amount <= 0
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    'The amount must be a valid number greater than zero.',
                ],
            ]);
        }

        $amount = round((float) $amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [
                    'The amount must be at least 0.01.',
                ],
            ]);
        }

        return $amount;
    }
}
