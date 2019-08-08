<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Factor
 */
class Factor extends Model
{
    protected $table = 'factor';

    public $timestamps = true;

    protected $fillable = [
        'factor_name'
    ];

    protected $guarded = [];

        
}