<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/25/17
 * Time: 4:06 PM
 */

namespace MentalHealthAI\Repositories\Implementations;

use MentalHealthAI\Models\Office;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;


class OfficeRepository implements IOfficeRepository
{


    /**
     * @return mixed
     */
    public function getAll($company_id)
    {
        $data = DB::table('office')
            ->select('office.*', DB::raw('COUNT(department.id) as count_emp'))
//            ->leftJoin('department', 'office.id', '=', 'department.office_id')
            ->leftJoin(DB::raw('(select id,office_id from department where is_active = true) department'),'department.office_id','=','office.id')
            ->where('office.company_id', '=', $company_id)
            ->where('office.is_active','=',true)
//            ->where('department.is_active','=',true)
            ->groupBy('office.id')
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getAllWithoutPaginate($company_id)
    {
        $data = DB::table('office')
            ->select('office.*', DB::raw('COUNT(department.id) as count_emp'))
            ->leftJoin('department', 'office.id', '=', 'department.office_id')
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->where('office.company_id', '=', $company_id)
            ->where('department.is_active','=',true)
            ->where('office.is_active','=',true)
            ->groupBy('office.id')->get();
        return $data;
    }

    public function getAllByOfficeId($office_id)
    {
        $data = DB::table('office')
            ->select('office.*', DB::raw('COUNT(department.id) as count_emp'))
            ->leftJoin('department', 'office.id', '=', 'department.office_id')
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->where('office.id', '=', $office_id)
            ->where('office.is_active','=',true)
            ->where('department.is_active','=',true)
            ->groupBy('office.id')
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getAllByOfficeIdWithoutPaginate($office_id)
    {
        $data = DB::table('office')
            ->select('office.*', DB::raw('COUNT(department.id) as count_emp'))
            ->leftJoin('department', 'office.id', '=', 'department.office_id')
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->where('office.id', '=', $office_id)
            ->where('office.is_active','=',true)
            ->where('department.is_active','=',true)
            ->groupBy('office.id')
            ->get();
        return $data;
    }

    public function getListOffices($company_id)
    {
        $data = Office::where('company_id', '=', $company_id)->get();
        return $data;
    }
    
    public function getListOfficeByDoctor($company_id, $userID)
    {
        $data = DB::table('office')
        ->select('office.*')
        ->leftJoin('company', 'company.id', '=', 'office.company_id')
        ->leftJoin('user_office','user_office.office_id','=','office.id')
        ->where('company.id', '=', $company_id)
        ->where('user_office.user_id','=', $userID)
        ->where('office.is_active','=',true)
        ->where('company.is_active','=',true)
        ->where('user_office.is_valid','=', 1)
        ->groupBy('office.id')
        ->get();
        return $data;
    }

    /**
     * @param $id
     * @return \Illuminate\Database\Eloquent\Collection|mixed|static[]
     */
    public function get($id)
    {
        return Office::where('id', '=', $id)->first();
    }

    /**
     * @param $id
     * @param $name
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search($id, $name, $cid)
    {
        $data = DB::table('office')
            ->select('office.*', DB::raw('COUNT(department.id) as count_emp'))
//            ->leftJoin('department', 'office.id', '=', 'department.office_id')
            ->leftJoin(DB::raw('(select id,office_id from department where is_active = true) department'),'department.office_id','=','office.id')
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->where('office.company_id', '=', $cid)
            ->where('office.is_active','=',true)
//            ->where('department.is_active','=',true)
            ->where(function ($query) use ($id,$name) {
                if(!IsNullOrEmptyString($id)){
                    $query->where('office.code', '=', $id);
                }
                if(!IsNullOrEmptyString($name)){
                    $query->where('office.id', '=', $name);
                }
            })
            ->groupBy('office.id')
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $deletedModel = $this->get($id);
        $deletedModel['isValid'] = false;
        $deletedModel->save();

    }

    /**
     * @param Office $office
     */
    public function save(Office $office)
    {
        $office->save();
        $office['code']=$office->id;
        $this->update($office->id,$office);
        return $office->id;
    }

    public function saveWithoutRegister(Office $office)
    {
        $office['is_active'] = false;
        $result = $office->save();
        $office['code']=$office->id;
        $this->update($office->id,$office);
        return $result;
    }

    /**
     * @param Office $office
     */
    public function update($id, Office $office)
    {
        return Office::where('id', '=', $id)->update($office->toArray());
    }


}