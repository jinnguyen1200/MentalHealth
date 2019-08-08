<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/4/17
 * Time: 2:32 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\Factor;
use MentalHealthAI\Models\FactorSettingValue;
use MentalHealthAI\Repositories\Interfaces\IFactorRepository;

class FactorRepository implements IFactorRepository
{

    public function getAll($group, $factorId)
    {
        $listFactor = Factor::where('factor_group','=', $group)->getModels();

        $defaultFactor = null;
        if (count($listFactor) > 0){
            if ($factorId > 0){
                foreach ($listFactor as $factor){
                    if ($factor->id == $factorId){
                        $defaultFactor = $factor;
                    }
                }
            }else{
                $defaultFactor = $listFactor[0];
            }
            $listFactorSettingValue = FactorSettingValue::where('factor_id', '=', $defaultFactor->id)
                ->where('valid_flag', '=', 1)
                ->orderBy('upper_limit')->getModels();

            if ($group == 5) {
                if ($listFactorSettingValue) {
                    foreach ($listFactorSettingValue as $fsv){
                        $fsv['point'] = sprintf("%06s", dechex($fsv->point));
                    }
                }
            }

            return array($listFactor, $listFactorSettingValue);
        }
        return array(null,null);


    }

    public function get($id)
    {
        Factor::where('id','=', $id)->first();
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function create(Factor $factor)
    {
        // TODO: Implement create() method.
    }

    public function update(Factor $factor)
    {
        // TODO: Implement update() method.
    }
}