<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/4/17
 * Time: 6:28 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\FactorSettingValue;

interface IFactorSettingValueRepository
{
    public function getAll($group, $factorId);

    public function get($id);

    public function getByFactorId($factorId);

    public function delete($id);

    public function create(FactorSettingValue $factor);

    public function update(FactorSettingValue $factor);
}