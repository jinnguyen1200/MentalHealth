<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/27/17
 * Time: 5:57 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\Administrator;
use MentalHealthAI\Models\UserCompany;
use MentalHealthAI\Models\UserOffice;
use MentalHealthAI\Repositories\Interfaces\IAdministratorRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IDepartmentRepository;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use MentalHealthAI\Repositories\Interfaces\IUserCompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IUserOfficeRepository;

class AdministratorRepository implements IAdministratorRepository
{

    protected $userCompanyRepository;
    protected $userOfficeRepository;


    /**
     * AdministratorRepository constructor.
     * @param IUserCompanyRepository $userCompanyRepository
     * @param IOfficeRepository $officeRepository
     * @param IDepartmentRepository $departmentRepository
     * @param ICompanyRepository $companyRepository
     * @param IUserOfficeRepository $userOfficeRepository
     */
    public function __construct(IUserCompanyRepository $userCompanyRepository, IOfficeRepository $officeRepository,
                                IDepartmentRepository $departmentRepository,
                                ICompanyRepository $companyRepository,
                                IUserOfficeRepository $userOfficeRepository)
    {
        $this->companyRepository = $companyRepository;
        $this->officeRepository = $officeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->userCompanyRepository = $userCompanyRepository;
        $this->userOfficeRepository = $userOfficeRepository;
    }

    public function search($name)
    {
        // TODO: Implement search() method.
    }

    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    public function save(Administrator $admin)
    {
        $admin->save();
    }

    public function getByEmail($email)
    {
        return Administrator::where('email', $email)->first();
    }

    public function getAllByCompanyId($id)
    {
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('company_id', '=', $id)
            ->where('office_id', '=', 0)
            ->where('department_id', '=', 0)
            ->where('account_type', '=', Config::get('constants.roles_company'))
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getAllByOfficeId($id)
    {
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('office_id', '=', $id)
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getPhysicianByCompanyId($id)
    {
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('company_id', '=', $id)
            ->where('account_type', '=', Config::get('constants.roles_doctor'))
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function delete($id)
    {
        $admin = Administrator::where('id', $id)->first();
        $admin['is_active'] = false;
        $admin->save();
        return $admin;
    }


    public function deleteWithCompany($adminId, $companyId)
    {
        $this->delete($adminId);
        $this->userCompanyRepository->deleteByUserAndCompany($adminId, $companyId);

    }

    public function deleteWithOffice($adminId, $officeId)
    {
        $this->delete($adminId);
        $this->userOfficeRepository->deleteByUserAndOffice($adminId, $officeId);

    }

    public function get($id)
    {
        return Administrator::where('id', $id)->first();
    }


    public function getAllByOfficeIdAndRole($id, $role)
    {
        $office = $this->officeRepository->get($id);
        $company = $this->companyRepository->get($office->company_id);
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('company_id', '=', $company->id)
            ->where('office_id', '=', $id)
            ->where('department_id', '=', 0)
            ->where('account_type', '=', $role)
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getAllByDepartmentIdAndRole($id, $role)
    {
        $department = $this->departmentRepository->get($id);
        $office = $this->officeRepository->get($department->office_id);
        $company = $this->companyRepository->get($office->company_id);
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('company_id', '=', $company->id)
            ->where('office_id', '=', $office->id)
            ->where('department_id', '=', $id)
            ->where('account_type', '=', $role)
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function saveWithCompany(Administrator $admin, $companyId)
    {
        $admin->save();
        $userCompany = $this->userCompanyRepository->getByUserAndCompany($admin['id'], $companyId);
        if (!$userCompany) {
            $userCompany = new UserCompany();
            $userCompany['user_id'] = $admin['id'];
            $userCompany['company_id'] = $companyId;
            $userCompany['is_valid'] = 1;
        }

        $userCompany->save();
    }

    public function saveWithOffice(Administrator $admin, $officeId)
    {
        $admin->save();
        $userOffice = $this->userOfficeRepository->getByUserAndOffice($admin['id'], $officeId);
        if (!$userOffice) {
            $userOffice = new UserOffice();
            $userOffice['user_id'] = $admin['id'];
            $userOffice['office_id'] = $officeId;
            $userOffice['is_valid'] = 1;
        }

        $userOffice->save();
    }


    public function getPhysicianAccountByCompany($companyId)
    {
        $ucList = $this->userCompanyRepository->getUsersByCompany($companyId);
        $ucIds = array();
        foreach ($ucList as $uc) {
            $ucIds[] = $uc['user_id'];
        }

        return $users = DB::table('administrator')->whereIn('id', $ucIds)->get();

    }

    public function getPhysicianAccountByOffice($officeId)
    {
        $ucList = $this->userOfficeRepository->getUsersByOffice($officeId);
        $ucIds = array();
        foreach ($ucList as $uc) {
            $ucIds[] = $uc['user_id'];
        }

        return $users = DB::table('administrator')->whereIn('id', $ucIds)->get();

    }

    public function getPhysicianByOfficeId($id)
    {
        $data = DB::table('administrator')
            ->select('administrator.*')
            ->where('office_id', '=', $id)
            ->where('account_type', '=', Config::get('constants.roles_doctor'))
            ->where('is_active', '=', true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }
}