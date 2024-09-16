<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class accessLogModel extends Model
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