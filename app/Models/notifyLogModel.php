<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notifyLogModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'notifyLogs';
    protected $primaryKey = 'notifyId';
    protected $keyType = 'string';

    protected $fillable = [
        'processId',
        'notifyName',
        'notifyResult',
        'notifyMessage',
        'notifyResponse'
    ];
}
