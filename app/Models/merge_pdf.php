<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class merge_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'hostName',
        'result',
        'err_reason',
        'err_api_reason',
        'uuid'
    ];
}
