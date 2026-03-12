<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentLog extends Model
{
    protected $fillable = [
        'title',
        'url',
        'keyword',
        'language',
        'status',
        'views',
        'revenue',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
