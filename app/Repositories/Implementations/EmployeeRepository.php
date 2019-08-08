<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 14/09/2017
 * Time: 06:29
 */

namespace MentalHealthAI\Repositories\Implementations;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use MentalHealthAI\Models\Employee;
use MentalHealthAI\Models\UploadHistory;
use MentalHealthAI\Repositories\Interfaces\IEmployeeRepository;
use Illuminate\Support\Facades\DB;
use MentalHealthAI\Repositories\Interfaces\IFactorSettingValueRepository;
use Mockery\Exception;

class EmployeeRepository implements IEmployeeRepository
{

    protected $factorSettingValueRepository;

    /**
     * Class constructor
     *
     * @param FactorSettingValueRepository $factorSettingValueRepository
     */
    public function __construct(IFactorSettingValueRepository $factorSettingValueRepository)
    {
        $this->factorSettingValueRepository = $factorSettingValueRepository;
    }

    public function getAll($mode)
    {
        if ($mode == null) {
            $list = Employee::all();
        } else if ($mode == 'sortID') {
            $list = Employee::orderBy('id', 'ASC')->get();
        } else if ($mode == 'sortDep') {
            $list = Employee::orderBy('department_id', 'ASC')->get();
        }
        return $list;
    }

    public function getForImport($company_id, $office_id, $department_id)
    {
        $list = DB::table('employee')
            ->select('employee.code as em','department.code as de','office.code as of','company.code as co','employee.id as em_id')
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('company', 'company.id', '=', 'office.company_id')
            ->where('company.id','=',$company_id)
            ->where('employee.valid_flag','=',1)
            ->where(function ($query) use ($office_id, $department_id) {
                if ($office_id !== 0) {
                    $query->where('office.id', '=', $office_id);
                }
                if ($department_id !== 0) {
                    $query->where('department.id', '=', $department_id);
                }
            })
            ->get();
        return $list;
    }

    public function get($id)
    {
        return Employee::where('id', '=', $id)->first();
    }

    public function delete($id)
    {
        $employee = $this->get($id);
        $employee['valid_flag'] = 0;
        return $this->update($employee,$id);
    }

    public function create(Employee $employee)
    {
        return $employee->save();
    }


    public function update(Employee $employee, $id)
    {
        return Employee::where('id', '=', $id)->update($employee->toArray());
    }

    public function search($id, $office, $department, $companyID, $userID)
    {
        $list = DB::table('employee')
            ->select('employee.*', DB::raw('COUNT(industrial_physician_comment_history.id) as count_comment'))
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('industrial_physician_comment_history', 'industrial_physician_comment_history.employee_id', '=', 'employee.id')
            ->leftJoin('user_office','user_office.office_id','=','office.id')
            ->leftJoin('company', 'company.id', '=', 'office.company_id')
            ->where(function ($query) use ($id, $office, $department, $companyID, $userID) {
                $query->where('user_office.user_id','=',$userID);
                $query->where('employee.valid_flag','=', 1);
                $query->where('company.is_active','=',true);
                $query->where('department.is_active','=',true);
                $query->where('office.is_active','=',true);
                $query->where('user_office.is_valid','=', 1);
                if (!IsNullOrEmptyString($companyID)) {
                    $query->where('company.id', '=', $companyID);
                }
                if (!IsNullOrEmptyString($id)) {
                    $query->where('employee.code', '=', $id);
                }
                if (!IsNullOrEmptyString($office)) {
                    $query->where('office.id', '=', $office);
                }
                if (!IsNullOrEmptyString($department)) {
                    $query->where('department.id', '=', $department);
                }
            })
            ->orderBy('employee.code')
            ->groupBy('employee.id')
            ->paginate(Config::get('constants.pagination_limit'));
        return $list;
    }

    public function importFile($employeeFile, $company_code, $office_code, $department_code, $listDepartment)
    {
        try {

            if (is_string($employeeFile)) {
                return "File name error!";
            }

            $path = $employeeFile->getRealPath();
            $name = $employeeFile->getClientOriginalName();
            if (!is_csv($name)) {
                return "File name error!";
            }
            $splitedFileName = preg_split("/[_.]/", $name);
            $period = $splitedFileName[1];
            $file_type = current($splitedFileName);
            if ($splitedFileName[0] !== "Employee") {
                return "適切なファイルをインポートしてください。例えば　Employee_201701.csv";
            }

            $oldUploadHitory =
                UploadHistory::where('file_type', $file_type)
                    ->where('period', '=', $period)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->where('valid_flag', '=', 1)->first();
            if ($oldUploadHitory) {
                $this->deleteAllByUploadFileID($oldUploadHitory->id);
                $oldUploadHitory['valid_flag'] = 0;
                $oldUploadHitory->save();
            }


            $uh = new UploadHistory();
            $uh['file_name'] = $name;
            $uh['file_type'] = $file_type;
            $uh['file_location'] = $path;
            $uh['status'] = 0;
            $uh['error_log'] = '';
            $uh['period'] = $period;
            $uh['company_id'] = Auth::user()->company_id;

            $office_id = Auth::user()->office_id;
            $department_id = Auth::user()->department_id;

            $data = Excel::load($path, function ($reader) {
                $reader->noHeading();
            })->get();
            $i = 0;
            if (!empty($data) && $data->count()) {
                $dataArray = $data->toArray();
                foreach ($dataArray as $row) {
                    if (!empty($row)) {

                        if (array_search($row, $dataArray) == 0) {
                            continue;
                        }//Skip header
                        $i++;
                        if(($result = array_search($row[2], array_column($listDepartment, 'de'))) !== false){
                            if($listDepartment[$result]->of != $row[1]){
                                throw new Exception('事業所コードが存在しません。 '.$i.'行目を確認してください。');
                            }

                            if($row[0] != $company_code){
                                throw new Exception('企業コードが存在しません。 '.$i.'行目を確認してください。');
                            }
                        }else{
                            throw new Exception('部署コードが存在しません。 '.$i.'行目を確認してください。');
                        }

                        if($office_id != 0){
                            if($office_code != $row[1]){
                                throw new Exception('No access for the office. '.$i.'行目を確認してください。');
                            }
                        }
                        if($department_id != 0){
                            if($department_code != $row[2]){
                                throw new Exception('No access for the department. '.$i.'行目を確認してください。');
                            }
                        }

                        $niceNames = array(
                            '0' => 'company_code',
                            '1' => 'office_code',
                            '2' => 'department_code',
                            '3' => 'employee_code',
                            '4' => 'birthdate',
                            '5' => 'position',
                            '6' => 'new_graduate_midway',
                            '7' => 'is_absence',
                            '8' => 'is_retirement',
                            '9' => 'entry_ym',
                            '10' => 'gender'
                        );

                        $validation = Validator::make($row, [
                            '0' => 'required|alpha_num',
                            '1' => 'required|alpha_num',
                            '2' => 'required|alpha_num',
                            '3' => 'required|alpha_num',
                            '4' => 'nullable|date_format:'.Config::get('constants.date_format'),
                            '5' => 'nullable|string|max:255',
                            '6' => 'nullable|integer',
                            '7' => 'nullable|integer',
                            '8' => 'nullable|integer',
                            '9' => 'nullable|integer',
                            '10' => 'nullable|alpha|in:M,F'
                        ]);
                        $validation->setAttributeNames($niceNames);
                        if ($validation->fails()) {
                            $all = "";
                            foreach ($validation->messages()->all() as $message){
                                $all = $all.$message.' ';
                            }
                            $all = $all.' もう一度' . $i . '行目を確認してください。';
                            throw new Exception($all);
                        }
                    }
                }
            }
            DB::beginTransaction();
            if (!empty($data) && $data->count()) {
                $dataArray = $data->toArray();
                foreach ($dataArray as $row) {
                    if (!empty($row)) {

                        if (array_search($row, $dataArray) == 0) {
                            continue;
                        }//Skip header
                        $result = array_search($row[2], array_column($listDepartment, 'de'));
                        $deID = $listDepartment[$result]->de_id;
                        Log::info($row);
                        $employee = new Employee(
                            ['code' => $row[3],
                                'department_id' => $deID,
                                'birthdate' => ($row[4] != null ? str_replace("/","-",$row[4]) : null),
                                'position' => $row[5],
                                'new_graduate_midway' => $row[6],
                                'is_absence' => $row[7],
                                'is_retirement' => $row[8],
                                'entry_ym' => $row[9],
                                'gender' => $row[10],
                                'upload_file_id' => $uh['id']]
                        );
                        $this->importEmployee($employee);
                    }
                }
            }
            DB::commit();
            $uh['status'] = 1;
            $uh->save();
        } catch (Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
        return null;
    }

    public function importEmployee(Employee $employee){
        $e = DB::table('employee')
            ->select('employee.*')
            ->leftJoin('department','department.id','=','employee.department_id')
            ->leftJoin('office','office.id','=','department.office_id')
            ->leftJoin('company','company.id','=','office.company_id')
            ->where('company.id','=',Auth::user()->company_id)
            ->where('employee.code','=',$employee->code)
            ->where('employee.valid_flag','=',1)
            ->get();
        if(!$e->isEmpty()){
            foreach ($e as $em){
                $employee['valid_flag'] = 1;
                $this->update($employee,$em->id);
            }
            return true;
        }else{
            return $this->create($employee);
        }
    }

    private function deleteAllByUploadFileID($uploadFileId)
    {
        Employee::where('upload_file_id', $uploadFileId)
            ->update(array('valid_flag' => false));
    }

    public function exportFile($company_id, $office_id, $department_id)
    {
        $list = DB::table('employee')
            ->select('employee.*','department.code as de','office.code as of','company.code as co')
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('company', 'office.company_id', '=', 'company.id')
            ->where('company.id', '=', $company_id)
            ->where(function ($query) use ($office_id, $department_id) {
                if ($office_id != 0) {
                    $query->where('office.id', '=', $office_id);
                    if (!IsNullOrEmptyString($department_id) && $department_id != 0) {
                        $query->where('department.id', '=', $department_id);
                    }
                }
            })
            ->where('employee.valid_flag','=',1)->get();
        return $list;
    }

    /*
    |--------------------------------------------------------------------------
    | Stress List
    |--------------------------------------------------------------------------
    */

    public function stress_index($company_id, $office, $department)
    {
        $monthFactor = $this->factorSettingValueRepository->getByFactorId(12);
        $month3Factor = $this->factorSettingValueRepository->getByFactorId(13);
        $month6Factor = $this->factorSettingValueRepository->getByFactorId(14);

        $month = date("Ym", strtotime(Carbon::now()));
        $employees = DB::table('employee')
            ->select(DB::raw('department.name as de_name'), 'employee.code', 'employee.id', DB::raw('COUNT(industrial_physician_comment_history.id) as count_comment'),DB::raw('coalesce(absentpoint.absentp, 0) as ap, coalesce(latepoint.latep, 0) as lp, coalesce(overpoint.overp, 0) as op, coalesce(bloodpoint.bloodp, 0) as bp
            , coalesce(currentmonth.cmonth, 0) as cmonth, coalesce(threemonth.tmonth, 0) as tmonth, coalesce(sixmonth.smonth, 0) as smonth
            , coalesce(stresspoint.scfpoint, 0) as sp, coalesce(avg_threemonth.avg_tmonth, 0) as avg_tmonth, coalesce(avg_sixmonth.avg_smonth, 0) as avg_smonth'))
            ->leftJoin('industrial_physician_comment_history', 'industrial_physician_comment_history.employee_id', '=', 'employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as absentp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (2,10) and valid_flg = 1
                        group by employee_id) as absentpoint'),'absentpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as latep
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (1,3) and valid_flg = 1
                        group by employee_id) as latepoint'),'latepoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as overp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (8) and valid_flg = 1
                        group by employee_id) as overpoint'),'overpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as bloodp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (9) and valid_flg = 1
                        group by employee_id) as bloodpoint'),'bloodpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as scfpoint
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (11) and valid_flg = 1
                        group by employee_id) as stresspoint'),'stresspoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as cmonth
                        from calculation_point
                        where period = \''.$month.'\' and valid_flg = 1
                        group by employee_id) as currentmonth'),'currentmonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as tmonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'3 months\', \'YYYYMM\') and valid_flg = 1
                        group by employee_id) as threemonth'),'threemonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, avg(point) as avg_tmonth
                        from calculation_point
                        where (period BETWEEN to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'3 months\', \'YYYYMM\') AND \''.$month.'\') and valid_flg = 1
                        group by employee_id) as avg_threemonth'),'avg_threemonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as smonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'6 months\', \'YYYYMM\') and valid_flg = 1
                        group by employee_id) as sixmonth'),'sixmonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point)/6 as avg_smonth
                        from calculation_point
                        where (period BETWEEN to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'6 months\', \'YYYYMM\') AND \''.$month.'\') and valid_flg = 1
                        group by employee_id) as avg_sixmonth'),'avg_sixmonth.employee_id','=','employee.id')
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('company', 'office.company_id', '=', 'company.id')
            ->where(function ($query) use ($company_id, $office, $department) {
                $query->where('company.id', '=', $company_id);
                if (!IsNullOrEmptyString($office)) {
                    $query->where('office.id', '=', $office);
                }
                if (!IsNullOrEmptyString($department)) {
                    $query->where('department.id', '=', $department);
                }
            })
            ->orderBy('code','ASC')
            ->where('employee.valid_flag','=',1)
            ->groupBy('employee.id', 'department.name', 'latepoint.latep', 'absentpoint.absentp', 'overpoint.overp', 'bloodpoint.bloodp'
                ,'currentmonth.cmonth','threemonth.tmonth','sixmonth.smonth','stresspoint.scfpoint','avg_threemonth.avg_tmonth','avg_sixmonth.avg_smonth')
            ->paginate(Config::get('constants.pagination_limit'));

        foreach ($employees as $e){
            $e->cmonthColor = 'FFFFFF';
            $e->cmonthColorText = '000000';
            foreach ($monthFactor as $m){
                if($e->cmonth >= $m['upper_limit']){
                    $first = $e->cmonthColor = sprintf("%06s", dechex($m->point));
                    $e->cmonthColorText = $this->getColor($first);
                    break;
                }
            }
            $e->tmonthColor = 'FFFFFF';
            $e->tmonthColorText = '000000';
            foreach ($month3Factor as $m){
                if($e->tmonth >= $m['upper_limit']){
                    $first = $e->tmonthColor = sprintf("%06s", dechex($m->point));
                    $e->tmonthColorText = $this->getColor($first);
                    break;
                }
            }
            $e->smonthColor = 'FFFFFF';
            $e->smonthColorText = '000000';
            foreach ($month6Factor as $m){
                if($e->smonth >= $m['upper_limit']){
                    $first = $e->smonthColor = sprintf("%06s", dechex($m->point));
                    $e->tmonthColorText = $this->getColor($first);
                    break;
                }
            }
        }
        return $employees;
    }

    public function stress_search($company_id, $office, $department, $id, $month, $column, $order)
    {
        $colList = array(
            'latep' => 'lp',
            'absentp' => 'ap',
            'overp' => 'op',
            'bloodp' => 'bp',
            'cmonth' => 'cmonth',
            'tmonth' => 'tmonth',
            'smonth' => 'smonth',
            'scfpoint' => 'sp'
        );

        $monthFactor = $this->factorSettingValueRepository->getByFactorId(12);
        $month3Factor = $this->factorSettingValueRepository->getByFactorId(13);
        $month6Factor = $this->factorSettingValueRepository->getByFactorId(14);

        if (array_key_exists($column, $colList)) {
            $column = $colList[$column];
        }else{
            $column = '';
        }

        if($order !== 'asc' && $order !== 'desc'){
            $order = '';
        }

        $employees = DB::table('employee')
            ->select(DB::raw('department.name as de_name'), 'employee.code', 'employee.id', DB::raw('COUNT(industrial_physician_comment_history.id) as count_comment'),DB::raw('coalesce(absentpoint.absentp, 0) as ap, coalesce(latepoint.latep, 0) as lp, coalesce(overpoint.overp, 0) as op, coalesce(bloodpoint.bloodp, 0) as bp
            , coalesce(currentmonth.cmonth, 0) as cmonth, coalesce(threemonth.tmonth, 0) as tmonth, coalesce(sixmonth.smonth, 0) as smonth
            , coalesce(stresspoint.scfpoint, 0) as sp, coalesce(avg_threemonth.avg_tmonth, 0) as avg_tmonth, coalesce(avg_sixmonth.avg_smonth, 0) as avg_smonth'))
            ->leftJoin('industrial_physician_comment_history', 'industrial_physician_comment_history.employee_id', '=', 'employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as absentp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (2,10) and valid_flg = 1
                        group by employee_id) as absentpoint'),'absentpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as latep
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (1,3) and valid_flg = 1
                        group by employee_id) as latepoint'),'latepoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as overp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (8) and valid_flg = 1
                        group by employee_id) as overpoint'),'overpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as bloodp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (9) and valid_flg = 1
                        group by employee_id) as bloodpoint'),'bloodpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as scfpoint
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (11) and valid_flg = 1
                        group by employee_id) as stresspoint'),'stresspoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as cmonth
                        from calculation_point
                        where period = \''.$month.'\' and valid_flg = 1
                        group by employee_id) as currentmonth'),'currentmonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as tmonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'3 months\', \'YYYYMM\') and valid_flg = 1
                        group by employee_id) as threemonth'),'threemonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point)/3 as avg_tmonth
                        from calculation_point
                        where (period BETWEEN to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'3 months\', \'YYYYMM\') AND \''.$month.'\') and valid_flg = 1
                        group by employee_id) as avg_threemonth'),'avg_threemonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as smonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'6 months\', \'YYYYMM\') and valid_flg = 1
                        group by employee_id) as sixmonth'),'sixmonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point)/6 as avg_smonth
                        from calculation_point
                        where (period BETWEEN to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'6 months\', \'YYYYMM\') AND \''.$month.'\') and valid_flg = 1
                        group by employee_id) as avg_sixmonth'),'avg_sixmonth.employee_id','=','employee.id')
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('company', 'office.company_id', '=', 'company.id')
            ->where(function ($query) use ($company_id, $office, $department, $id) {
                $query->where('company.id', '=', $company_id);
                if (!IsNullOrEmptyString($office)) {
                    $query->where('office.id', '=', $office);
                }
                if (!IsNullOrEmptyString($department)) {
                    $query->where('department.id', '=', $department);
                }
                if (!IsNullOrEmptyString($id)) {
                    $query->where('employee.code', '=', $id);
                }
            })
            ->orderByRaw((!IsNullOrEmptyString($column) && !IsNullOrEmptyString($order) ? $column.' '.$order.', ' : '')." code ASC")
            ->where('employee.valid_flag','=',1)
            ->groupBy('employee.id', 'department.name', 'latepoint.latep', 'absentpoint.absentp', 'overpoint.overp', 'bloodpoint.bloodp'
                ,'currentmonth.cmonth','threemonth.tmonth','sixmonth.smonth','stresspoint.scfpoint','avg_threemonth.avg_tmonth','avg_sixmonth.avg_smonth')
            ->paginate(Config::get('constants.pagination_limit'));

        foreach ($employees as $e){
            $e->cmonthColor = 'FFFFFF';
            $e->cmonthColorText = '000000';
            foreach ($monthFactor as $m){
                if($e->cmonth >= $m['upper_limit']){
                    $first = $e->cmonthColor = sprintf("%06s", dechex($m->point));
                    $e->cmonthColorText = $this->getColor($first);
                    break;
                }
            }
            $e->tmonthColor = 'FFFFFF';
            $e->tmonthColorText = '000000';
            foreach ($month3Factor as $m){
                if($e->tmonth >= $m['upper_limit']){
                    $first = $e->tmonthColor = sprintf("%06s", dechex($m->point));
                    $e->tmonthColorText = $this->getColor($first);
                    break;
                }
            }
            $e->smonthColor = 'FFFFFF';
            $e->smonthColorText = '000000';
            foreach ($month6Factor as $m){
                if($e->smonth >= $m['upper_limit']){
                    $first = $e->smonthColor = sprintf("%06s", dechex($m->point));
                    $e->tmonthColorText = $this->getColor($first);
                    break;
                }
            }
        }
        return $employees;
    }

    public function getColor($color){
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return ($yiq >= 128) ? 'black' : 'white';
    }

    public function stress_export($company_id, $office, $department, $id, $month)
    {
        $employees = DB::table('employee')
            ->select(DB::raw('department.name as de_name'), 'employee.code', 'employee.id', DB::raw('COUNT(industrial_physician_comment_history.id) as count_comment'),DB::raw('coalesce(absentpoint.absentp, 0) as ap, coalesce(latepoint.latep, 0) as lp, coalesce(overpoint.overp, 0) as op, coalesce(bloodpoint.bloodp, 0) as bp
            , coalesce(currentmonth.cmonth, 0) as cmonth, coalesce(threemonth.tmonth, 0) as tmonth, coalesce(sixmonth.smonth, 0) as smonth'))
            ->leftJoin('industrial_physician_comment_history', 'industrial_physician_comment_history.employee_id', '=', 'employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as absentp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (2) and valid_flg in (1)
                        group by employee_id) as absentpoint'),'absentpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as latep
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (1) and valid_flg in (1)
                        group by employee_id) as latepoint'),'latepoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as overp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (8) and valid_flg in (1)
                        group by employee_id) as overpoint'),'overpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as bloodp
                        from calculation_point
                        where period = \''.$month.'\'
                        and factor_id in (3) and valid_flg in (1)
                        group by employee_id) as bloodpoint'),'bloodpoint.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as cmonth
                        from calculation_point
                        where period = \''.$month.'\' and valid_flg in (1)
                        group by employee_id) as currentmonth'),'currentmonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as tmonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'3 months\', \'YYYYMM\') and valid_flg in (1)
                        group by employee_id) as threemonth'),'threemonth.employee_id','=','employee.id')
            ->leftJoin(DB::raw('(select employee_id, sum(point) as smonth
                        from calculation_point
                        where period = to_char(to_date(\''.$month.'\',\'YYYYMM\') - INTERVAL \'6 months\', \'YYYYMM\') and valid_flg in (1)
                        group by employee_id) as sixmonth'),'sixmonth.employee_id','=','employee.id')
            ->leftJoin('department', 'employee.department_id', '=', 'department.id')
            ->leftJoin('office', 'department.office_id', '=', 'office.id')
            ->leftJoin('company', 'office.company_id', '=', 'company.id')
            ->where(function ($query) use ($company_id, $office, $department, $id) {
                $query->where('company.id', '=', $company_id);
                if (!IsNullOrEmptyString($office)) {
                    $query->where('office.id', '=', $office);
                }
                if (!IsNullOrEmptyString($department)) {
                    $query->where('department.id', '=', $department);
                }
                if (!IsNullOrEmptyString($id)) {
                    $query->where('employee.code', '=', $id);
                }
            })
            ->orderBy('employee.code')
            ->where('employee.valid_flag','=',1)
            ->groupBy('employee.id', 'department.name', 'latepoint.latep', 'absentpoint.absentp', 'overpoint.overp', 'bloodpoint.bloodp'
                ,'currentmonth.cmonth','threemonth.tmonth','sixmonth.smonth')->get();
        return $employees;
    }


}