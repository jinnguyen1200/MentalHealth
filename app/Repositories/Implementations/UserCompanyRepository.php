<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/27/17
 * Time: 10:32 AM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\UserCompany;
use MentalHealthAI\Models\UserOffice;
use MentalHealthAI\Repositories\Interfaces\IUserCompanyRepository;

class UserCompanyRepository implements IUserCompanyRepository
{

    public function get($id)
    {
        return UserCompany::where('id',$id)
            ->where('is_valid', '=', 1)
            ->first();
    }

    public function getUsersByCompany($companyId)
    {
        return UserCompany::where('company_id', '=', $companyId)
            ->where('is_valid', '=', 1)
            ->getModels();
    }

    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    public function save(UserCompany $uc)
    {
        $uc->save();
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function getByUserAndCompany($userId, $companyId)
    {
        return UserCompany::where('user_id','=',$userId)
            ->where('company_id','=' ,$companyId)
            ->where('is_valid', '=', 1)
            ->first();
    }


    public function deleteByUserAndCompany($userId, $companyId)
    {
        $uc = $this->getByUserAndCompany($userId, $companyId);
        if ($uc){
            $uc['is_valid'] = 0;
            return $this->save($uc);
        }
        return;
    }


}