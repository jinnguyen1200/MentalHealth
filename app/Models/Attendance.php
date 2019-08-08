<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Attendance
 */
class Attendance extends Model
{
    protected $table = 'attendance';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'attendence_num',
        'absence_num',
        'overtime_hours',
        'late_times',
        'monday_absence',
        'monday_late_arrival',
        'measuring_date',
        'registration_date',
        'period',
        'upload_file_id',
        'valid_flag'
    ];

    protected $guarded = [];

        
}