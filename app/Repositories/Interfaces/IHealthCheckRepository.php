<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/10/17
 * Time: 4:16 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\HealthCheck;

interface IHealthCheckRepository
{
    public function search($name);

    public function get($id);

    public function getAll($company_id, $office_id, $department_id);

    public function save(HealthCheck $healthCheck);

    public function saveHealthCheckFromFile($healthCheckFile, $employeeList, $currentList);

    public function delete($id);
}