<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 25/09/2017
 * Time: 14:56
 */

namespace MentalHealthAI\Repositories\Implementations;


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use MentalHealthAI\Models\Company;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements ICompanyRepository
{
    public function getAll()
    {
        $list = DB::table('company')
            ->select('company.*',DB::raw('industry.name as nameI'),DB::raw('COUNT(office.id) as total'))
            ->join('industry','company.industry_id','=','industry.id')
            ->leftJoin(DB::raw('(select id,company_id from office where is_active = true) office'),'office.company_id','=','company.id')
            ->groupBy('company.id','industry.name')
            ->where('company.is_active','=',true)
            ->paginate(Config::get('constants.pagination_limit'));

        return $list;
    }
    
    public function getAllDoctorCompany($id)
    {
        $list = DB::table('company')
        ->select('company.*')
        ->leftJoin('office','office.company_id','=','company.id')
        ->leftJoin('user_office','user_office.office_id','=','office.id')
        ->groupBy('company.id')
        ->where('user_office.user_id','=',$id)
        ->where('company.is_active','=',true)
        ->where('office.is_active','=',true)
        ->where('user_office.is_valid','=', 1)
        ->get();
        
        return $list;
    }

    public function search($id, $idname)
    {
        $list = DB::table('company')
            ->select('company.*',DB::raw('industry.name as nameI'),DB::raw('COUNT(office.id) as total'))
            ->join('industry','company.industry_id','=','industry.id')
            ->leftJoin(DB::raw('(select id,company_id from office where is_active = true) office'),'office.company_id','=','company.id')
//            ->leftJoin('office','office.company_id','=','company.id')
//            ->leftJoin('department','office.id','=','department.office_id')
//            ->leftJoin('employee','department.id','=','employee.department_id')
            ->groupBy('company.id','industry.name')
            ->where('company.is_active','=',true)
            ->where(function ($query) use ($id, $idname){
                if(!IsNullOrEmptyString($id)){
                    $query->where('company.code','=',$id);
                }
                if(!IsNullOrEmptyString($idname)){
                    $query->where('company.id','=',$idname);
                }
            })
            ->paginate(Config::get('constants.pagination_limit'));
        return $list;
    }

    public function get($id)
    {
        return Company::where('id', '=', $id)->first();
    }

    public function update(Company $company, $id)
    {
        return Company::where('id', '=', $id)->update($company->toArray());
    }

    public function save(Company $company)
    {
        $result = $company->save();
        $company['code'] = $company->id;
        $this->update($company,$company->id);
        return $company->id;
    }

    public function saveWithoutRegister(Company $company)
    {
        $company['is_active'] = false;
        $result = $company->save();
        $company['code'] = $company->id;
        $this->update($company,$company->id);
        return $result;
    }

    public function getAllWithoutPagination()
    {
        $list = DB::table('company')
            ->select('company.*',DB::raw('industry.name as nameI'),DB::raw('COUNT(office.id) as total'))
            ->join('industry','company.industry_id','=','industry.id')
//            ->leftJoin('office','office.company_id','=','company.id')
            ->leftJoin(DB::raw('(select id,company_id from office where is_active = true) office'),'office.company_id','=','company.id')
//            ->leftJoin('department','office.id','=','department.office_id')
//            ->leftJoin('employee','department.id','=','employee.department_id')
            ->groupBy('company.id','industry.name')
            ->where('company.is_active','=',true)
            ->get();

        return $list;
    }
}