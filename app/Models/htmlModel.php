<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class htmlModel extends Model
{
    use HasFactory;

    protected $table = 'pdfHtml';

    protected $fillable = [
        'urlName',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
