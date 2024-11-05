<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mergeModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'pdfMerge';
    protected $primaryKey = 'mergeId';
    protected $keyType = 'string';

    protected $fillable = [
        'fileName',
        'fileSize',
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
