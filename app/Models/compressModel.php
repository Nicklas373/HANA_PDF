<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class compressModel extends Model
{
    use HasFactory;

    protected $table = 'pdfCompress';

    protected $fillable = [
        'fileName',
        'fileSize',
        'compFileSize',
        'compMethod',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
