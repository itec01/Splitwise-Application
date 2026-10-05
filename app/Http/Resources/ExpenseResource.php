<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->_id,

            'group_id' => (string) $this->group_id,

            'description' => $this->description,

            'amount' => $this->amount,

            'paid_by' => (string) $this->paid_by,

            'split_type' => $this->split_type,

            'participants' => collect($this->participants ?? [])
                ->map(function ($participant) {
                    return [
                        'user_id' => (string) $participant['user_id'],
                        'amount' => $participant['amount'] ?? null,
                        'percentage' => $participant['percentage'] ?? null,
                    ];
                })
                ->values()
                ->all(),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}