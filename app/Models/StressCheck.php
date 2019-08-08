<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StressCheck
 */
class StressCheck extends Model
{
    protected $table = 'stress_check';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'implementation_date',
        'is_present',
        'is_high_stress_judgement',
        'registration_date',
        'period',
        'upload_file_id',
        'valid_flag'
    ];

    protected $guarded = [];

        
}