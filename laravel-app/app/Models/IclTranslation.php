<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IclTranslation extends Model
{
    protected $primaryKey = 'translation_id';
    protected $connection = 'wordpress';

    protected $fillable = [
        'translation_id',
        'element_type',
        'element_id',
        'trid',
        'language_code',
        'source_language_code',
    ];
}
