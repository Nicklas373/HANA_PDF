<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pdf_word extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileName',
        'fileSize',
        'hostName',
    ];
}