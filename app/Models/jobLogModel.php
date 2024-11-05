<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jobLogModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'jobLogs';
    protected $primaryKey = 'jobsId';
    protected $keyType = 'string';

    protected $fillable = [
        'jobsName',
        'jobsEnv',
        'jobsRuntime',
        'jobsResult',
        'groupId',
        'processId',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
