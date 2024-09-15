<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompressModel extends Model
{
    use HasFactory;

    protected $table = 'pdfCompress';

    protected $fillable = [
        'compressId',
        'fileName',
        'fileSize',
        'compFileSize',
        'compMethod',
        'result',
        'isBatch',
        'batchId',
        'procStartAt',
        'procEndAt',
        'procDuration',
        'isReport'
    ];
}
