<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'file_name',
        'original_name',
        'file_type',
        'file_size',
        'file_path',
    ];
}
