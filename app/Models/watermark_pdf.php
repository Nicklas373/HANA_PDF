<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class watermark_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'hostName',
        'watermarkText',
        'watermarkPage',
        'watermarkFontStyle',
        'watermarkFontSize',
        'watermarkFontTransparency'
    ];
}