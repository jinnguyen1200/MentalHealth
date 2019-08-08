<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserOffice
 */
class UserOffice extends Model
{
    protected $table = 'user_office';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'office_id',
        'is_valid'
    ];

    protected $guarded = [];

        
}