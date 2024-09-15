<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HtmlModel extends Model
{
    use HasFactory;

    protected $table = 'pdfHtml';

    protected $fillable = [
        'htmlId',
        'urlName',
        'urlMargin',
        'urlOrientation',
        'urlSinglePage',
        'urlSize',
        'result',
        'procStartAt',
        'procEndAt',
        'procDuration',
        'isReport'
    ];
}
