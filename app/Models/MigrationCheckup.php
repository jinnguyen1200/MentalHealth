<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MigrationCheckup
 */
class MigrationCheckup extends Model
{
    protected $table = 'migration_checkup';

    public $timestamps = false;

    protected $fillable = [
        'business_unit',
        'department',
        'consultation_number',
        'gender',
        'birth_of_date',
        'employee_number',
        'consultation_date',
        'body_height',
        'weight',
        'abdominal_girth',
        'standard_weight',
        'bmi_index',
        'body_mass',
        'measurement_judge',
        'uncorrected_eyesight_right',
        'uncorrected_eyesight_left',
        'corrected_eyesight_right',
        'corrected_eyesight_left',
        'eyesight_judge',
        'hearing_1000_right',
        'hearing_1000_left',
        'hearing_4000_right',
        'hearing_4000_left',
        'hearing_judge',
        'max_blood_pressure_1st',
        'min_blood_pressure_1st',
        'max_blood_pressure_2nd',
        'min_blood_pressure_2nd',
        'antihypertensive_drug',
        'blood_pressure_judge',
        'period_flag',
        'uric_protein',
        'urinary_sugar',
        'urine_judge',
        'electrocardiogram_id',
        'electrocardiogram_comment1',
        'electrocardiogram_judge',
        'chest_xray_id',
        'chest_xray_method',
        'chest_xray_comment1',
        'chest_xray_comment2',
        'chest_xray_judge',
        'internal_medicine_comment1',
        'internal_medicine_comment2',
        'internal_medicine_judge',
        'blood_draw_id',
        'erythrocyte_count',
        'hemoglobin_content',
        'anemia_test_judge',
        'lipid_drug',
        'neutral_lipid',
        'hdl_cholesterol',
        'ldl_cholesterol',
        'lipid',
        'got',
        'gpt',
        'gamma_gtp',
        'liver_function_judge',
        'blood_glucose_drug',
        'fasting_blood_glucose',
        'after_food',
        'diabetes_judge',
        'uric_acid',
        'gout_judge',
        'total_judge'
    ];

    protected $guarded = [];

        
}