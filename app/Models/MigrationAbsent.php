<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MigrationAbsent
 */
class MigrationAbsent extends Model
{
    protected $table = 'migration_absent';

    public $timestamps = false;

    protected $fillable = [
        'employee_number',
        'department',
        'place',
        'contract_form',
        'late_total',
        'absence_total',
        'late_11',
        'absence_11',
        'late_12',
        'absence_12',
        'late_01',
        'absence_01',
        'late_02',
        'absence_02',
        'late_03',
        'absence_03',
        'late_04',
        'absence_04',
        'note'
    ];

    protected $guarded = [];

        
}