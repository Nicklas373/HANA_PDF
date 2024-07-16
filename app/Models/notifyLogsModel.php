<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notifyLogsModel extends Model
{
    use HasFactory;

    protected $table = 'notifyLogs';

    protected $fillable = [
        'processId',
        'notifyName',
        'notifyResult',
        'notifyMessage',
        'notifyResponse'
    ];
}
