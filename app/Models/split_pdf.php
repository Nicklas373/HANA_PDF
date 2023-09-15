<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class split_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'hostName',
        'fromPage',
        'toPage',
        'customPage',
        'fixedPage',
        'fixedPageRange',
        'mergePDF',
        'result',
        'err_reason',
        'err_api_reason',
        'uuid'
    ];
}
