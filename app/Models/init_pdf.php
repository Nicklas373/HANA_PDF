<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class init_pdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'err_reason',
        'err_api_reason',
    ];
}
