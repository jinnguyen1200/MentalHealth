<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Office
 */
class Office extends Model
{
    protected $table = 'office';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'company_id',
        'name',
        'size',
        'point_factor',
        'street_address',
        'contact_phone_number',
        'contact_name',
        'is_active'
    ];

    protected $guarded = [];


    public function company() {

        return $this->belongsTo('MentalHealthAI\Models\Company')->first();
    }

    /**
     * Get the departments for the office post.
     */
    public function department()
    {
        return $this->hasMany('MentalHealthAI\Models\Department', 'office_id', 'id')->get();
    }
}