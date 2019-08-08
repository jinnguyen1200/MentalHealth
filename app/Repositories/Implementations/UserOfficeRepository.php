<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 11/9/17
 * Time: 2:14 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\UserOffice;
use MentalHealthAI\Repositories\Interfaces\IUserOfficeRepository;

class UserOfficeRepository implements IUserOfficeRepository
{

    public function get($id)
    {
        return UserOffice::where('id',$id)
            ->where('is_valid', '=', 1)
            ->first();
    }

    public function getByUserAndOffice($userId, $officeId)
    {
        return UserOffice::where('user_id','=',$userId)
            ->where('office_id','=' ,$officeId)
            ->where('is_valid', '=', 1)
            ->first();
    }

    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    public function getUsersByOffice($officeId)
    {
        return UserOffice::where('office_id', '=', $officeId)
            ->where('is_valid', '=', 1)
            ->getModels();
    }

    public function save(UserOffice $uc)
    {
        $uc->save();
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function deleteByUserAndOffice($userId, $officeId)
    {
        $uc = $this->getByUserAndOffice($userId, $officeId);
        if ($uc){
            $uc['is_valid'] = 0;
            return $this->save($uc);
        }
        return;
    }
}