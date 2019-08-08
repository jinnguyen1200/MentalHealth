<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserCompany
 */
class UserCompany extends Model
{
    protected $table = 'user_company';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'company_id',
        'is_valid'
    ];

    protected $guarded = [];

        
}