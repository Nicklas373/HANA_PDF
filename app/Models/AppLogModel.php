<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLogModel extends Model
{
    use HasFactory;

    protected $table = 'appLogs';

    protected $fillable = [
        'processId',
        'errReason',
        'errStatus'
    ];
}