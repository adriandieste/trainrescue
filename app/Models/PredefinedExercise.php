<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredefinedExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'technical_description',
        'materials',
        'is_active',
    ];

    protected $casts = [
        'materials' => 'array',
        'is_active' => 'boolean',
    ];
}

