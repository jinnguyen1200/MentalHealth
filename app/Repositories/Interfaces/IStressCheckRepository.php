<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/10/17
 * Time: 4:17 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\StressCheck;

interface IStressCheckRepository
{
    public function search($name);

    public function get($id);

    public function getAll($company_id, $office_id, $department_id);

    public function save(StressCheck $stressCheck);

    public function saveStressCheckFromFile($stressCheckFile, $employeeList, $currentList);

    public function delete($id);
}