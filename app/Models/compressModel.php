<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class compressModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $primaryKey = 'compressId';
    protected $table = 'pdfCompress';
    protected $keyType = 'string';

    protected $fillable = [
        'fileName',
        'fileSize',
        'compFileSize',
        'compMethod',
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
