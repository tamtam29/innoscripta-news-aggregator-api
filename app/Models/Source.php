<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'name',
        'description',
        'url',
        'category',
        'language',
        'country',
        'provider',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}