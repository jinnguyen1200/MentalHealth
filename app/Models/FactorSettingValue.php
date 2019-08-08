<?php

namespace MentalHealthAI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class FactorSettingValue
 */
class FactorSettingValue extends Model
{
    protected $table = 'factor_setting_value';

    public $timestamps = true;

    protected $fillable = [
        'factor_id',
        'upper_limit',
        'point'
    ];

    protected $guarded = [];

    public function getIndustryName($id){

        $sql = "select name from industry;";
        $industryNames = DB::select($sql);

//        $industryNames = ['農業、林業', '漁業', '鉱業、採石業、砂利採取業', '建設業', '製造業', '電気・ガス・熱供給・水道業',
//            '情報通信業','運輸業、郵便業','卸売業・小売業','金融業、保険業','不動産業、物品賃貸業','学術研究、専門・技術サービス業',
//            '宿泊業、飲食店','生活関連サービス業、娯楽業','教育学習支援業','医療、福祉','複合サービス事業'];
        return $industryNames[$id -1]->name;
    }

        
}