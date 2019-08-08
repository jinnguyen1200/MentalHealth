<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/22/17
 * Time: 11:20 AM
 */

namespace MentalHealthAI\Repositories\Implementations;


use MentalHealthAI\Models\Department;
use MentalHealthAI\Repositories\Interfaces\IDepartmentRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DepartmentRepository implements IDepartmentRepository
{

    public function getAll($office_id)
    {
        $data = DB::table('department')
            ->select('department.*', DB::raw('COUNT(employee.id) as count_emp'))
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->leftJoin(DB::raw('(select id,department_id from employee where valid_flag = 1) employee'),'employee.department_id','=','department.id')
            ->groupBy('department.id')
            ->orderBy('count_emp', 'desc')
            ->where('department.office_id','=',$office_id)
            ->where('department.is_active','=',true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function getAllByCompanyID($company_id)
    {
        $data = DB::table('department')
            ->select('department.code as de','office.code as of','department.id as de_id')
            ->leftJoin('office','office.id','=','department.office_id')
            ->where('office.company_id','=',$company_id)
            ->where('department.is_active','=',true)->get();
        return $data;
    }

    public function getAllByOfficeID($office_id)
    {
        $data = DB::table('department')
            ->select('department.*', DB::raw('COUNT(employee.id) as count_emp'))
            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->groupBy('department.id')
            ->where('department.office_id','=',$office_id)
            ->where('department.is_active','=',true)->get();
        return $data;
    }

    public function getAllByDepartmentID($departmentId)
    {
        $data = DB::table('department')
            ->select('department.*', DB::raw('COUNT(employee.id) as count_emp'))
            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->groupBy('department.id')
            ->orderBy('count_emp', 'desc')
            ->where('department.id','=',$departmentId)
            ->where('department.is_active','=',true)
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function get($id)
    {
        return Department::where('id', '=', $id) -> first();
    }

    public function search($office_id, $id, $name)
    {
        $data = DB::table('department')
            ->select('department.*', DB::raw('COUNT(employee.id) as count_emp'))
//            ->leftJoin('employee', 'employee.department_id', '=', 'department.id')
            ->leftJoin(DB::raw('(select id,department_id from employee where valid_flag = 1) employee'),'employee.department_id','=','department.id')
            ->groupBy('department.id')
            ->orderBy('count_emp', 'desc')
            ->where('department.office_id','=',$office_id)
            ->where('department.is_active','=',true)
            ->where(function ($query) use ($id,$name) {
                if(!IsNullOrEmptyString($id)){
                    $query->where('department.code', '=', $id);
                }
                if(!IsNullOrEmptyString($name)){
                    $query->where('department.id', '=', $name);
                }
            })
            ->paginate(Config::get('constants.pagination_limit'));
        return $data;
    }

    public function delete($id)
    {
        $deletedModel = $this->get($id);
        $deletedModel['isValid'] = false;
        $deletedModel -> save();

    }

    public function save(Department $department)
    {
        $department -> save();
        if($department['code'] == null){
            $department['code'] = $department->id;
            $this->update($department->id,$department);
        }
        return $department;
    }

    public function saveWithoutRegister(Department $department)
    {
        $department['is_active'] = false;
        $department -> save();
        $department['code'] = $department->id;
        $this->update($department->id,$department);
        return $department;
    }

    public function update($id,Department $department)
    {
        return Department::where('id','=',$id)->update($department->toArray());
    }

    public function import(Department $department)
    {
        $d = Department::where('code','=',$department->code)->get();
        if(!$d->isEmpty()){
            foreach ($d as $de){
                $this->update($de->id,$department);
            }
        }else{
            $this->save($department);
        }
    }

}