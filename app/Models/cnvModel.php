<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cnvModel extends Model
{
    use HasFactory;

    protected $table = 'pdfConvert';

    protected $fillable = [
        'cnvId',
        'fileName',
        'fileSize',
        'container',
        'imgExtract',
        'result',
        'isBatch',
        'batchId',
        'procStartAt',
        'procEndAt',
        'procDuration',
        'isReport'
    ];
}
