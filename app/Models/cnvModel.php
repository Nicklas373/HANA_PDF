<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cnvModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'container',
        'imgExtract',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
