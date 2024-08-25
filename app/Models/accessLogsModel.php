<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class accessLogsModel extends Model
{
    use HasFactory;

    protected $table = 'accessLogs';

    protected $fillable = [
        'accessId',
        'processId',
        'routePath',
        'accessIpAddress'
    ];
}
