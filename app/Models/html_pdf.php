<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class html_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'urlName',
        'result',
        'err_reason',
        'err_api_reason'
    ];
}
