<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Company
 */
class Company extends Model
{
    protected $table = 'company';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'industry_id',
        'name',
        'point_factor',
        'street_address',
        'contact_phone_number',
        'contact_name',
        'is_active'
    ];

    protected $guarded = [];


    /**
     * Get the departments for the office post.
     */
    public function office()
    {
        return $this->hasMany('MentalHealthAI\Models\Office', 'company_id', 'id')->get();
    }
}