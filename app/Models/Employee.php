<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee
 */
class Employee extends Model
{
    protected $table = 'employee';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'company_id',
        'office_id',
        'department_id',
        'gender',
        'birthdate',
        'position',
        'entry_ym',
        'new_graduate_midway',
        'is_absence',
        'is_retirement',
        'upload_file_id',
        'valid_flag'
    ];

    protected $guarded = [];

    public function department() {

        return $this->belongsTo('MentalHealthAI\Models\Department');
    }
        
}