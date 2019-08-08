<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 8/31/17
 * Time: 2:36 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;

use MentalHealthAI\Models\Employee;

interface IEmployeeRepository
{
    public function getAll($mode);

    public function getForImport($company_id, $office_id, $department_id);

    public function get($id);

    public function delete($id);

    public function create(Employee $employee);

    public function update(Employee $employee, $id);

    public function search($id, $office, $department, $companyID, $userID);

    public function importEmployee(Employee $employee);

    public function importFile($employeeFile, $company_code, $office_code, $department_code, $listDepartment);

    public function exportFile($company_id, $office_id, $department_id);

    /*
    |--------------------------------------------------------------------------
    | Stress List
    |--------------------------------------------------------------------------
    */

    public function stress_index($company_id, $office, $department);

    public function stress_search($company_id, $office, $department, $id, $month, $column, $order);

    public function stress_export($company_id, $office, $department, $id, $month);


}