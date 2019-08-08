<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MigrationOvertime
 */
class MigrationOvertime extends Model
{
    protected $table = 'migration_overtime';

    public $timestamps = false;

    protected $fillable = [
        'employee_number',
        'department',
        'base',
        'adoption_result',
        'contract_form',
        'over_80_hours',
        'ot_11',
        'ot_12',
        'ot_01',
        'ot_02',
        'ot_03',
        'ot_04'
    ];

    protected $guarded = [];

        
}