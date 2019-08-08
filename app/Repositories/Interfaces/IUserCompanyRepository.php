<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/27/17
 * Time: 10:30 AM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\UserCompany;

interface IUserCompanyRepository
{
    public function get($id);

    public function getByUserAndCompany($userId, $companyId);

    public function getAll();

    public function getUsersByCompany($companyId);

    public function save(UserCompany $admin);

    public function delete($id);

    public function deleteByUserAndCompany($userId, $companyId);

}