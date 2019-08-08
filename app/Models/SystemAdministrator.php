<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SystemAdministrator
 */
class SystemAdministrator extends Model
{
    protected $table = 'system_administrator';

    public $timestamps = true;

    protected $fillable = [
        'password',
        'mail_address'
    ];

    protected $guarded = [];

        
}