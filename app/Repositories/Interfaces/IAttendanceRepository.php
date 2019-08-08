<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/10/17
 * Time: 3:53 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\Attendance;

interface IAttendanceRepository
{
    public function search($name);

    public function get($id);

    public function getAll($company_id, $office_id, $department_id);

    public function save(Attendance $attendance);

    public function saveAttendanceFromFile($attendanceFile, $employeeList, $currentList);

    public function delete($id);
}