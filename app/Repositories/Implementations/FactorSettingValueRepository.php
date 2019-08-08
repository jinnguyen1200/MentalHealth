<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/4/17
 * Time: 6:29 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\FactorSettingValue;
use MentalHealthAI\Repositories\Interfaces\IFactorSettingValueRepository;

class FactorSettingValueRepository implements IFactorSettingValueRepository
{

    public function getAll($group, $factorId)
    {
        // TODO: Implement getAll() method.
    }

    public function get($id)
    {
        return FactorSettingValue::where('id','=', $id)->first();
    }

    public function delete($id)
    {
        $factor = $this->get($id);
        if ($factor){
            $factor['valid_flag'] = 0;
            $factor->save();
        }
    }

    public function create(FactorSettingValue $factor)
    {
        $factor->save();
    }

    public function update(FactorSettingValue $factor)
    {
        $factor->save();
    }

    public function getByFactorId($factorId)
    {
        return FactorSettingValue::where('factor_id','=', $factorId)
            ->where('valid_flag' , '=', 1)
            ->orderBy('upper_limit', 'DESC')->getModels();
    }
}