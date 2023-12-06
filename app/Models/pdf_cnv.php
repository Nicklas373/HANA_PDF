<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pdf_cnv extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'container',
        'result',
        'err_reason',
        'err_api_reason'
    ];
}
