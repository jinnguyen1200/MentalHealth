<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Industry
 */
class Industry extends Model
{
    protected $table = 'industry';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'point_factor'
    ];

    protected $guarded = [];

        
}