<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRecord extends Model {
    protected $table = 'sync_records';
    protected $fillable = [
        'user',
        'updated',
        'inserted',
        'errors',
        'status',
    ];
}
