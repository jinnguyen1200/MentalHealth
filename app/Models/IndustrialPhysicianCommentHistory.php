<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class IndustrialPhysicianCommentHistory
 */
class IndustrialPhysicianCommentHistory extends Model
{
    protected $table = 'industrial_physician_comment_history';

    public $timestamps = true;

    protected $fillable = [
        'company_id',
        'office_id',
        'department_id',
        'employee_id',
        'comment',
        'interview_date',
        'registration_date'
    ];

    protected $guarded = [];

    public function employee() {

        return $this->belongsTo('MentalHealthAI\Models\Employee');
    }

        
}