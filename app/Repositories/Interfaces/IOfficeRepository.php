<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/25/17
 * Time: 3:56 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\Office;

interface IOfficeRepository
{
    public function getAll($company_id);

    public function getAllWithoutPaginate($company_id);

    public function getAllByOfficeId($office_id);

    public function getAllByOfficeIdWithoutPaginate($office_id);

    public function get($id);

    public function search($id, $name, $cid);

    public function delete($id);

    public function save(Office $office);

    public function saveWithoutRegister(Office $office);

    public function update($id,Office $office);
    
    public function getListOffices($company_id);
    
    public function getListOfficeByDoctor($company_id, $userID);

}