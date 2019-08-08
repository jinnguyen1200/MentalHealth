<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UploadHistory
 */
class UploadHistory extends Model
{
    protected $table = 'upload_history';

    public $timestamps = true;

    protected $fillable = [
        'file_name',
        'file_location',
        'file_type',
        'status',
        'error_log',
        'company_id',
        'period',
        'valid_flag'

    ];

    protected $guarded = [];

        
}