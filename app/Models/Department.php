<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * Class Department
 */
class Department extends Model
{
    protected $table = 'department';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'company_id',
        'office_id',
        'name',
        'point_factor',
        'contact_phone_number',
        'contact_name',
        'is_active'
    ];

    protected $guarded = [];

    /**
     * Get the employees for the department post.
     */
    public function employees()
    {
        return $this->hasMany('MentalHealthAI\Models\Employee', 'department_id', 'id');
    }

    /**
     * Get the employees for the department post.
     */
    public function countEmployees()
    {
        return $this->employees()->count();
    }

    public function office() {

        return $this->belongsTo('MentalHealthAI\Models\Office')->first();
    }
        
}