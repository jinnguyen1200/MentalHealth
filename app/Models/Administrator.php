<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Administrator
 */
class Administrator extends Model
{
    protected $table = 'administrator';

    public $timestamps = true;

    protected $fillable = [
        'account_type',
        'company_id',
        'office_id',
        'department_id',
        'email',
        'password',
        'is_active'
    ];

    protected $guarded = [];

        
}