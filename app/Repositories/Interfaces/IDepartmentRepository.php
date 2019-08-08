<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/22/17
 * Time: 11:17 AM
 */

namespace MentalHealthAI\Repositories\Interfaces;

use MentalHealthAI\Models\Department;

interface IDepartmentRepository
{
    public function getAll($office_id);

    public function getAllByCompanyID($company_id);

    public function getAllByDepartmentID($departmentId);

    public function get($id);

    public function search($office_id, $id, $name);

    public function delete($id);

    public function save(Department $department);

    public function saveWithoutRegister(Department $department);

    public function update($id,Department $department);

    public function import(Department $department);
    
    public function getAllByOfficeID($office_id);

}