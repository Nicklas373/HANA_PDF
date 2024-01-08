<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mergeModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
