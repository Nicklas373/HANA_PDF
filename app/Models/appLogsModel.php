<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class appLogsModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'err_reason',
        'err_api_reason'
    ];
}
