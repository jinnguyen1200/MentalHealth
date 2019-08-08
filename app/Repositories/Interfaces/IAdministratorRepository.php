<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/27/17
 * Time: 5:57 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;
use MentalHealthAI\Models\Administrator;


interface IAdministratorRepository
{
    public function search($name);

    public function get($id);

    public function getByEmail($email);

    public function getAll();

    public function getPhysicianAccountByCompany($companyId);

    public function getPhysicianAccountByOffice($companyId);

    public function save(Administrator $admin);

    public function saveWithCompany(Administrator $admin, $companyId);

    public function saveWithOffice(Administrator $admin, $officeId);

    public function getAllByCompanyId($id);

    public function getAllByOfficeId($id);

    public function getPhysicianByCompanyId($id);

    public function getPhysicianByOfficeId($id);

    public function getAllByOfficeIdAndRole($id,$role);

    public function getAllByDepartmentIdAndRole($id,$role);

    public function delete($id);

    public function deleteWithCompany($adminId, $companyId);

    public function deleteWithOffice($adminId, $office);
}