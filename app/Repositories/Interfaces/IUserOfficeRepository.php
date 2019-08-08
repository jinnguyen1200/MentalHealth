<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 11/9/17
 * Time: 2:12 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\UserOffice;

interface IUserOfficeRepository
{
    public function get($id);

    public function getByUserAndOffice($userId, $officeId);

    public function getAll();

    public function getUsersByOffice($officeId);

    public function save(UserOffice $admin);

    public function delete($id);

    public function deleteByUserAndOffice($userId, $companyId);
}