<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class deleteModel extends Model
{
    use HasFactory;

    protected $table = 'pdfDelete';

    protected $fillable = [
        'fileName',
        'fileSize',
        'deletePage',
        'mergePDF',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
