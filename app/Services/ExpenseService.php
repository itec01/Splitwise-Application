<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        protected GroupService $groupService
    ) {
    }

    /**
     * Create an expense.
     */
    public function createExpense(
        Group $group,
        array $data,
        User $user
    ): Expense {
        // Authenticated user must belong to the group.
        $this->groupService->ensureMember($group, $user);

        $memberIds = collect($group->member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        /*
         * Validate payer.
         */
        $paidBy = (string) $data['paid_by'];

        if (!in_array($paidBy, $memberIds, true)) {
            throw ValidationException::withMessages([
                'paid_by' => [
                    'The payer must be a member of this group.'
                ],
            ]);
        }

        /*
         * Validate participants.
         */
        $participants = $data['participants'];

        $participantIds = collect($participants)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        /*
         * Prevent duplicate participants.
         */
        if (count($participantIds) !== count(array_unique($participantIds))) {
            throw ValidationException::withMessages([
                'participants' => [
                    'A participant cannot be added more than once.'
                ],
            ]);
        }

        /*
         * Every participant must belong to the group.
         */
        foreach ($participantIds as $participantId) {
            if (!in_array($participantId, $memberIds, true)) {
                throw ValidationException::withMessages([
                    'participants' => [
                        "User {$participantId} is not a member of this group."
                    ],
                ]);
            }
        }

        /*
         * Calculate shares.
         */
        $calculatedParticipants = $this->calculateParticipants(
            $data['split_type'],
            (float) $data['amount'],
            $participants
        );

        /*
         * Create expense.
         */
        $expense = Expense::create([
            'group_id' => (string) $group->getKey(),
            'description' => $data['description'],
            'amount' => round((float) $data['amount'], 2),
            'paid_by' => $paidBy,
            'split_type' => $data['split_type'],
            'participants' => $calculatedParticipants,
        ]);

        Log::info('Expense created', [
            'expense_id' => (string) $expense->getKey(),
            'group_id' => (string) $group->getKey(),
            'paid_by' => $paidBy,
        ]);

        return $expense;
    }

    /**
     * Calculate participant shares according to split type.
     */
    protected function calculateParticipants(
        string $splitType,
        float $total,
        array $participants
    ): array {
        return match ($splitType) {
            'equal' => $this->calculateEqualSplit(
                $total,
                $participants
            ),

            'exact' => $this->calculateExactSplit(
                $total,
                $participants
            ),

            'percentage' => $this->calculatePercentageSplit(
                $total,
                $participants
            ),

            default => throw ValidationException::withMessages([
                'split_type' => [
                    'Invalid split type.'
                ],
            ]),
        };
    }

    /**
     * Equal split.
     */
    protected function calculateEqualSplit(
        float $total,
        array $participants
    ): array {
        $count = count($participants);

        if ($count === 0) {
            throw ValidationException::withMessages([
                'participants' => [
                    'At least one participant is required.'
                ],
            ]);
        }

        /*
         * Work in cents to avoid floating-point
         * rounding problems.
         */
        $totalCents = (int) round($total * 100);

        $baseCents = intdiv($totalCents, $count);

        $remainder = $totalCents % $count;

        $result = [];

        foreach ($participants as $index => $participant) {
            $shareCents = $baseCents;

            /*
             * Distribute remaining cents.
             *
             * Example:
             * 100 / 3
             *
             * 33.34
             * 33.33
             * 33.33
             */
            if ($index < $remainder) {
                $shareCents++;
            }

            $result[] = [
                'user_id' => (string) $participant['user_id'],
                'amount' => round($shareCents / 100, 2),
            ];
        }

        return $result;
    }

    /**
     * Exact split.
     */
    protected function calculateExactSplit(
        float $total,
        array $participants
    ): array {
        $totalCents = (int) round($total * 100);

        $participantTotalCents = 0;

        foreach ($participants as $participant) {
            if (!array_key_exists('amount', $participant)) {
                throw ValidationException::withMessages([
                    'participants' => [
                        'Each participant must have an amount for exact splitting.'
                    ],
                ]);
            }

            $amount = (float) $participant['amount'];

            if ($amount < 0) {
                throw ValidationException::withMessages([
                    'participants' => [
                        'Participant amount cannot be negative.'
                    ],
                ]);
            }

            $participantTotalCents += (int) round($amount * 100);
        }

        if ($participantTotalCents !== $totalCents) {
            throw ValidationException::withMessages([
                'participants' => [
                    'The exact participant amounts must equal the total expense amount.'
                ],
            ]);
        }

        return collect($participants)
            ->map(function ($participant) {
                return [
                    'user_id' => (string) $participant['user_id'],
                    'amount' => round((float) $participant['amount'], 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Percentage split.
     */
    protected function calculatePercentageSplit(
        float $total,
        array $participants
    ): array {
        $percentageTotal = 0;

        foreach ($participants as $participant) {
            if (!array_key_exists('percentage', $participant)) {
                throw ValidationException::withMessages([
                    'participants' => [
                        'Each participant must have a percentage for percentage splitting.'
                    ],
                ]);
            }

            $percentage = (float) $participant['percentage'];

            if ($percentage < 0 || $percentage > 100) {
                throw ValidationException::withMessages([
                    'participants' => [
                        'Percentage must be between 0 and 100.'
                    ],
                ]);
            }

            $percentageTotal += $percentage;
        }

        /*
         * Percentages must equal exactly 100%.
         */
        if (abs($percentageTotal - 100) > 0.00001) {
            throw ValidationException::withMessages([
                'participants' => [
                    'Participant percentages must equal 100%.'
                ],
            ]);
        }

        $totalCents = (int) round($total * 100);

        $result = [];
        $calculatedCents = 0;

        foreach ($participants as $index => $participant) {
            $percentage = (float) $participant['percentage'];

            /*
             * Give the final participant any rounding remainder.
             */
            if ($index === count($participants) - 1) {
                $amountCents = $totalCents - $calculatedCents;
            } else {
                $amountCents = (int) round(
                    $totalCents * ($percentage / 100)
                );

                $calculatedCents += $amountCents;
            }

            $result[] = [
                'user_id' => (string) $participant['user_id'],
                'percentage' => $percentage,
                'amount' => round($amountCents / 100, 2),
            ];
        }

        return $result;
    }

    /**
     * Find an expense.
     */
    public function findExpense(
        string $expenseId,
        User $user
    ): Expense {
        $expense = Expense::find($expenseId);

        if (!$expense) {
            throw ValidationException::withMessages([
                'expense' => [
                    'Expense not found.'
                ],
            ]);
        }

        $group = Group::find($expense->group_id);

        if (!$group) {
            throw ValidationException::withMessages([
                'group' => [
                    'Expense group not found.'
                ],
            ]);
        }

        $this->groupService->ensureMember($group, $user);

        return $expense;
    }

    /**
     * Get expenses for a group.
     */
    public function getGroupExpenses(
        Group $group,
        User $user,
        array $filters = []
    ) {
        $this->groupService->ensureMember($group, $user);

        $query = Expense::where(
            'group_id',
            (string) $group->getKey()
        );

        if (!empty($filters['payer'])) {
            $query->where(
                'paid_by',
                (string) $filters['payer']
            );
        }

        if (!empty($filters['split_type'])) {
            $query->where(
                'split_type',
                $filters['split_type']
            );
        }

        if (!empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                \Carbon\Carbon::parse($filters['date_from'])
                    ->startOfDay()
            );
        }

        if (!empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                \Carbon\Carbon::parse($filters['date_to'])
                    ->endOfDay()
            );
        }

        $perPage = min(
            (int) ($filters['per_page'] ?? 15),
            100
        );

        return $query
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Update an expense.
     */
    public function updateExpense(
        Expense $expense,
        array $data,
        User $user
    ): Expense {
        $group = Group::find($expense->group_id);

        if (!$group) {
            throw ValidationException::withMessages([
                'group' => [
                    'Expense group not found.'
                ],
            ]);
        }

        $this->groupService->ensureMember($group, $user);

        /*
         * For now, allow the group owner or current payer
         * to update the expense.
         */
        $userId = (string) $user->getKey();

        if (
            (string) $group->owner_id !== $userId &&
            (string) $expense->paid_by !== $userId
        ) {
            throw ValidationException::withMessages([
                'expense' => [
                    'Only the group owner or expense payer can update this expense.'
                ],
            ]);
        }

        $memberIds = collect($group->member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->all();

        $paidBy = (string) $data['paid_by'];

        if (!in_array($paidBy, $memberIds, true)) {
            throw ValidationException::withMessages([
                'paid_by' => [
                    'The payer must be a member of this group.'
                ],
            ]);
        }

        $participantIds = collect($data['participants'])
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (
            count($participantIds) !==
            count(array_unique($participantIds))
        ) {
            throw ValidationException::withMessages([
                'participants' => [
                    'A participant cannot be added more than once.'
                ],
            ]);
        }

        foreach ($participantIds as $participantId) {
            if (!in_array($participantId, $memberIds, true)) {
                throw ValidationException::withMessages([
                    'participants' => [
                        "User {$participantId} is not a member of this group."
                    ],
                ]);
            }
        }

        $calculatedParticipants = $this->calculateParticipants(
            $data['split_type'],
            (float) $data['amount'],
            $data['participants']
        );

        $expense->update([
            'description' => $data['description'],
            'amount' => round((float) $data['amount'], 2),
            'paid_by' => $paidBy,
            'split_type' => $data['split_type'],
            'participants' => $calculatedParticipants,
        ]);

        return $expense->fresh();
    }

    /**
     * Delete an expense.
     */
    public function deleteExpense(
        Expense $expense,
        User $user
    ): void {
        $group = Group::find($expense->group_id);

        if (!$group) {
            throw ValidationException::withMessages([
                'group' => [
                    'Expense group not found.'
                ],
            ]);
        }

        $this->groupService->ensureMember($group, $user);

        $userId = (string) $user->getKey();
        $payerId = (string) $expense->paid_by;

    if ($payerId !== $userId) {
            throw ValidationException::withMessages([
                'expense' => [
                    'Only the member who paid for this expense can delete it.'
                ],
            ]);
        }

        $expense->delete();

        Log::info('Expense deleted', [
            'expense_id' => (string) $expense->getKey(),
            'group_id' => (string) $group->getKey(),
        ]);
    }
}