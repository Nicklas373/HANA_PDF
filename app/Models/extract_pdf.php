<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class extract_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'customPage',
        'hostName',
        'mergePDF',
        'result',
        'err_reason',
        'err_api_reason',
        'uuid'
    ];
}
