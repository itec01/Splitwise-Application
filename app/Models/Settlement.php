<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

class Settlement extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'settlements';

    protected $fillable = [
        'group_id',
        'paid_by',
        'paid_to',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * The group this settlement belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * The user who paid the settlement.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * The user who received the settlement.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_to');
    }
}
