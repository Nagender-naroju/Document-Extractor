<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'file_path',
        'extracted_data',
        'status'
    ];

    protected $casts = [
        'extracted_data' => 'array'
    ];
}
