<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class appLogModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'appLogs';
    protected $primaryKey = 'processId';
    protected $keyType = 'string';

    protected $fillable = [
        'processId',
        'groupId',
        'errReason',
        'errStatus'
    ];
}
