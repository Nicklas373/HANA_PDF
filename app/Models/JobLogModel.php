<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLogModel extends Model
{
    use HasFactory;

    protected $table = 'jobLogs';

    protected $fillable = [
        'jobsId',
        'jobsName',
        'jobsEnv',
        'jobsRuntime',
        'jobsResult',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
