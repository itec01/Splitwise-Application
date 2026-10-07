<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Attachment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'attachments';

    protected $fillable = [
        'settlement_id',
        'file_id',
        'file_name',
        'file_size',
        'mime_type',
    ];
}
