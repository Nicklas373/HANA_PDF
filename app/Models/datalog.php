<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class datalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'jobsId',
        'jobsName',
        'jobsEnv',
        'jobsRuntime',
        'jobsResult',
        'jobsErrMessage',
        'jobsStart',
        'jobsEnd'
    ];
}
