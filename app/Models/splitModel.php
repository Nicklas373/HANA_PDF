<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SplitModel extends Model
{
    use HasFactory;

    protected $table = 'pdfSplit';

    protected $fillable = [
        'splitId',
        'fileName',
        'fileSize',
        'fromPage',
        'toPage',
        'customPage',
        'fixedPage',
        'fixedPageRange',
        'mergePDF',
        'action',
        'result',
        'isBatch',
        'batchId',
        'procStartAt',
        'procEndAt',
        'procDuration',
        'isReport'
    ];
}
