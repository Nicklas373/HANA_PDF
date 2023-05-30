<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class compression_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'compMethod',
        'hostName'
    ];
}