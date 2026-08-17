<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'post_id',
    'file_name',
    'file_path',
    'file_type',
    'mime_type',
    'file_size',
    'url',
])]
class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }
}
