<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class splitModel extends Model
{
    use HasFactory;

    protected $table = 'pdfSplit';

    protected $fillable = [
        'fileName',
        'fileSize',
        'fromPage',
        'toPage',
        'customPage',
        'fixedPage',
        'fixedPageRange',
        'mergePDF',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
