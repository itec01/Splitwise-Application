<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Group extends Model
{
    protected $collection = 'groups';

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'member_ids',
    ];

    protected $casts = [
        // 'member_ids' => 'array',
    ];
}
