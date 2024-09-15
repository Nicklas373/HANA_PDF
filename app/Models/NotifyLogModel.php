<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifyLogModel extends Model
{
    use HasFactory;

    protected $table = 'notifyLogs';

    protected $fillable = [
        'notifyId',
        'processId',
        'notifyName',
        'notifyResult',
        'notifyMessage',
        'notifyResponse'
    ];
}
