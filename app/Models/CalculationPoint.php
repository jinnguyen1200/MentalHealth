<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CalculationPoint
 */
class CalculationPoint extends Model
{
    protected $table = 'calculation_point';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'factor_id',
        'point',
        'period',
        'created_by',
        'note_1',
        'note_2',
        'note_3',
        'valid_flg'
    ];

    protected $guarded = [];

        
}