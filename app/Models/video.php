<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class video extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_path',
        'thumbnail_path',
        'uploaded_by',
        'is_published',
        'order',
    ];
}
