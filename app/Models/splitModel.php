<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class splitModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'pdfSplit';
    protected $primaryKey = 'splitId';
    protected $keyType = 'string';

    protected $fillable = [
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
        'batchName',
        'groupId',
        'processId',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
