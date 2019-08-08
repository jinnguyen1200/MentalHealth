<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 25/09/2017
 * Time: 14:57
 */

namespace MentalHealthAI\Repositories\Interfaces;

use MentalHealthAI\Models\Company;

interface ICompanyRepository
{

    public function get($id);

    public function getAll();
    
    public function getAllDoctorCompany($id);

    public function getAllWithoutPagination();

    public function search($id, $idname);

    public function update(Company $company, $id);

    public function save(Company $company);

    public function saveWithoutRegister(Company $company);
}