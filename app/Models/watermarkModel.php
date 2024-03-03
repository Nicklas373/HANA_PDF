<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class watermarkModel extends Model
{
    use HasFactory;

    protected $table = 'pdfWatermark';

    protected $fillable = [
        'fileName',
        'fileSize',
        'watermarkFontFamily',
        'watermarkFontStyle',
        'watermarkFontSize',
        'watermarkFontTransparency',
        'watermarkImage',
        'watermarkLayout',
        'watermarkMosaic',
        'watermarkRotation',
        'watermarkStyle',
        'watermarkText',
        'watermarkPage',
        'result',
        'isBatch',
        'batchId',
        'procStartAt',
        'procEndAt',
        'procDuration'
    ];
}
