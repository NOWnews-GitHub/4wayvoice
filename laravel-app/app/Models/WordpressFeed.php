<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordpressFeed extends Model
{
    protected $fillable = [
        'source',
        'post_id',
        'wp_post_id',
        'created_at',
        'updated_at',
    ];
}
