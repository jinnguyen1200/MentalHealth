<?php

namespace MentalHealthAI\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MentalHealthAI\Models\HealthCheck;
use MentalHealthAI\Models\StressCheck;
use MentalHealthAI\Models\UploadHistory;
use MentalHealthAI\Repositories\Interfaces\IAttendanceRepository;
use MentalHealthAI\Repositories\Interfaces\ICalculationPointRepository;
use MentalHealthAI\Repositories\Interfaces\IDepartmentRepository;
use MentalHealthAI\Repositories\Interfaces\IEmployeeRepository;
use MentalHealthAI\Repositories\Interfaces\IHealthCheckRepository;
use MentalHealthAI\Repositories\Interfaces\IIndustrialPhysicianCommentHistoryRepository;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use MentalHealthAI\Repositories\Interfaces\IStressCheckRepository;
use MentalHealthAI\User;
use Mockery\Exception;
use Psy\Util\Json;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;

class EmployeeController extends Controller
{
    protected $companyRepository;
    protected $officeRepository;
    protected $departmentRepository;
    protected $employeeRepository;
    protected $attendanceRepository;
    protected $healthCheckRepository;
    protected $stressCheckRepository;
    protected $industrialPhysicianCommentHistoryRepository;
    protected $calculationRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(IEmployeeRepository $employeeRepository,
                                IOfficeRepository $officeRepository,
                                IDepartmentRepository $departmentRepository,
                                IAttendanceRepository $attendanceRepository,
                                IHealthCheckRepository $healthCheckRepository,
                                IStressCheckRepository $stressCheckRepository,
                                IIndustrialPhysicianCommentHistoryRepository $industrialPhysicianCommentHistoryRepository,
                                ICalculationPointRepository $calculationPointRepository,
                                ICompanyRepository $companyRepository)
    {
        $this->employeeRepository = $employeeRepository;
        $this->officeRepository = $officeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->healthCheckRepository = $healthCheckRepository;
        $this->stressCheckRepository = $stressCheckRepository;
        $this->industrialPhysicianCommentHistoryRepository = $industrialPhysicianCommentHistoryRepository;
        $this->calculationRepository = $calculationPointRepository;
        $this->companyRepository = $companyRepository;
        $this->middleware('auth');
        $this->middleware('roles:' . User::DOCTOR . ',' . User::COMPANY);
    }

    /**
     * Get company ID
     *
     *
     */
    public function getCompanyID()
    {
        return Auth::user()->company_id;
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(Request $request)
    {
        Validator::make($request->all(), [
            'employee_num' => 'required|alpha_num|max:100',
            'department_id' => 'required|integer',
            'birthdate' => 'nullable|date_format:'.Config::get('constants.date_format').'|before_or_equal:'.date(Config::get('constants.date_format'), strtotime(Carbon::now())),
            'position' => 'nullable|string|max:100',
            'entry_ym' => 'nullable|date_format:Y/m|before_or_equal:'.date('Y/m', strtotime(Carbon::now())),
            'new_graduate_midway' => 'nullable|string',
            'retirement' => 'nullable|string',
            'absence' => 'nullable|string',
            'sex' => 'nullable|alpha|in:M,F'
        ])->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $companyID = $this->getCompanyID();
        $officeList = $this->officeRepository->getAll($companyID);
        $userID = Auth::user()->id;
        //Company list on search screen
        $companyList = $this->companyRepository->getAllDoctorCompany($userID);
        
        //Load employee list when page is loaded
        $employee = $this->employeeRepository->search(null, null, null, null, $userID);
        //Handle data from employee
        if ($employee != null) {
            foreach ($employee as $employeeTmp) {
                $entryDay = str_split($employeeTmp->entry_ym, 4);
                if ($employeeTmp->entry_ym != '' && strlen($employeeTmp->entry_ym) >= 5) {
                    $employeeTmp->entry_ym = $entryDay[0] . "/" . $entryDay[1];
                }
                //Get all from department by department_id
                $departmentList = $this->departmentRepository->get($employeeTmp->department_id);
                //Set department->name into $employee named departmentName
                $employeeTmp->departmentName = $departmentList->name;
                //Set format Y/m/d for birth_date
                if($employeeTmp->birthdate != '' ){
                    $employeeTmp->birthdate = date('Y/m/d',strtotime($employeeTmp->birthdate));
                }
                //Set data for $employeeTmp->is_absence
                if ($employeeTmp->is_absence !== ''){
                    if ($employeeTmp->is_absence === true) {
                        $employeeTmp->is_absence = '休職';
                    } if ($employeeTmp->is_absence === false) {
                        $employeeTmp->is_absence = '通常';
                    }
                }
                
                //Set data for $employeeTmp->new_graduate_midway
                if ($employeeTmp->new_graduate_midway !== '') {
                    if ($employeeTmp->new_graduate_midway === true) {
                        $employeeTmp->new_graduate_midway = '新卒';
                    } if ($employeeTmp->new_graduate_midway === false) {
                        $employeeTmp->new_graduate_midway = '中途';
                    }
                }
                
                //Set data for $employeeTmp->is_retirement
                if ($employeeTmp->is_retirement !== '') {
                    if ($employeeTmp->is_retirement === true) {
                        $employeeTmp->is_retirement = '退職';
                    } if ($employeeTmp->is_retirement === false) {
                        $employeeTmp->is_retirement = '在職中';
                    }
                }
                
                //Set gender for employee
                if ($employeeTmp->gender !== '') {
                    if ($employeeTmp->gender == 'M') {
                        $employeeTmp->gender = '男性';
                    }
                    else if ($employeeTmp->gender == 'F') {
                        $employeeTmp->gender = '女性';
                    }else{
                        $employeeTmp->gender = '';
                    }
                }
            }
        }
        
//        $departmentList = $this->departmentRepository->getAll();
        return view('doctor.employee')->with('office', $officeList)->with('employee', $employee)->with('company', $companyList);
    }
    
    public function getOffice(Request $request)
    {
        $userID = Auth::user()->id;
        $listOffice =  $this->officeRepository->getListOfficeByDoctor($request['cbxCompanyName'], $userID);
        return $listOffice;
    }
    
    public function getDepartment(Request $request)
    {
        $listDepart = $this->departmentRepository->getAllByOfficeID($request['cbxOfficeName']);
        return $listDepart;
    }
    
    public function createIndex($d = null)
    {
        $this->checkRole(User::COMPANY);
        $departments = [];
        $office_id = Auth::user()->office_id;
        if ($office_id != 0) {
            $office = $this->officeRepository->get($office_id);
            $department_id = Auth::user()->department_id;

            if ($department_id != 0) {
                $departments = $this->departmentRepository->get($department_id);
            } else {
                $departments = $this->departmentRepository->getAllByOfficeID($office_id)->toArray();
            }
        } else {
            $office = $this->officeRepository->getListOffices(Auth::user()->company_id)->toArray();
        }

        if($d != null){
            $currentOffice = $this->officeRepository->get($this->departmentRepository->get($d)->office_id);
            $departments = $this->departmentRepository->getAllByOfficeID($currentOffice->id)->toArray();
        }
        return view('company.employee-new')->with('listOffices', $office)->with('listDepartments', $departments)
            ->with('currentDepartment', $d)->with('currentOffice',($d == null? null : $currentOffice->id));
    }

    public function editIndex($e)
    {
        $this->checkRole(User::COMPANY);
        $employee = $this->employeeRepository->get($e);
        $currentDe = $this->departmentRepository->get($employee->department_id);
        $currentOf = $this->officeRepository->get($currentDe->office_id);
        $departments = [];
            $office_id = Auth::user()->office_id;
            if ($office_id != 0) {
                $office = $this->officeRepository->get($office_id);
                $department_id = Auth::user()->department_id;
                if ($department_id != 0) {
                    $departments = $this->departmentRepository->get($department_id);
                } else {
                    $departments = $this->departmentRepository->getAllByOfficeID($office_id)->toArray();
                }
            } else {
                $office = $this->officeRepository->getListOffices(Auth::user()->company_id)->toArray();
                $departments = $this->departmentRepository->getAllByOfficeID($currentOf->id)->toArray();
            }


        return view('company.employee-edit')->with('listOffices', $office)->with('listDepartments', $departments)
            ->with('currentDe',$currentDe)->with('currentOf',$currentOf)->with('employee',$employee);
    }

    /**
     * Get data for comment list
     *
     * @return \Illuminate\Http\Response
     */
    public function comment($id)
    {
        $employee = $this->employeeRepository->get($id);
        $listComment = $this->industrialPhysicianCommentHistoryRepository->getCommentList($id);
        foreach ($listComment as $list){
            if($list->interview_date != ''){
                $list->interview_date = date('Y/m/d',strtotime($list->interview_date));
            }
        }
        return view('doctor.commentList')->with('listComment', $listComment)->with('empID', $id)->with('empCode', $employee->code);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->checkRole(User::COMPANY);
        if(Auth::user()->department_id != 0){
            $request['department_id'] = Auth::user()->department_id;
        }
        $this->validator($request);
        $employee = new Employee(
            ['code' => $request['employee_num'],
                'department_id' => $request['department_id'],
                'birthdate' => ($request['birthdate'] != null ? str_replace("/","-",$request['birthdate']) : null),
                'position' => ($request['position'] != null ? $request['position'] : null),
                'new_graduate_midway' => ($request['new_graduate_midway'] != "" ?
                    ($request['new_graduate_midway'] == 'New graduate' ? true : false) : null),
                'is_absence' => ($request['absence'] != "" ?
                    ($request['absence'] == 'absence' ? true : false) : null),
                'is_retirement' => ($request['retirement'] != "" ?
                    ($request['retirement'] == 'retirement' ? true : false) : null),
                'entry_ym' => ($request['entry_ym'] != null ? str_replace("/","",$request['entry_ym']) : null),
                'gender' => ($request['sex'] != "" ? $request['sex'] : null)]
        );
        $notification = $this->employeeRepository->importEmployee($employee);
        if ($notification) {
            alert()->success('', '登録が完了しました。');
        } else {
            alert()->error('', 'エラー。');
        }
        $backSearch = $request->session()->get('stress-search');
        return redirect(route('stress-search',$backSearch));
    }

    public function edit(Request $request)
    {
        $this->checkRole(User::COMPANY);
        if(Auth::user()->department_id != 0){
            $request['department_id'] = Auth::user()->department_id;
        }
        $this->validator($request);
        $employee = new Employee(
            ['code' => $request['employee_num'],
                'department_id' => $request['department_id'],
                'birthdate' => ($request['birthdate'] != null ? str_replace("/","-",$request['birthdate']) : null),
                'position' => ($request['position'] != null ? $request['position'] : null),
                'new_graduate_midway' => ($request['new_graduate_midway'] != "" ?
                    ($request['new_graduate_midway'] == 'New graduate' ? true : false) : null),
                'is_absence' => ($request['absence'] != "" ?
                    ($request['absence'] == 'absence' ? true : false) : null),
                'is_retirement' => ($request['retirement'] != "" ?
                    ($request['retirement'] == 'retirement' ? true : false) : null),
                'entry_ym' => ($request['entry_ym'] != null ? str_replace("/","",$request['entry_ym']) : null),
                'gender' => ($request['sex'] != "" ? $request['sex'] : null)]
        );
        $notification = $this->employeeRepository->update($employee, $request['employee_id']);
        if ($notification) {
            alert()->success('', '変更しました。');
        } else {
            alert()->error('', 'エラーが発生したので、変更されませんでした。');
        }
        $backSearch = $request->session()->get('stress-search');
        return redirect(route('stress-search',$backSearch));
    }


    public function employee_delete(Request $request)
    {
        return $this->employeeRepository->delete($request['employee_id']);
    }

    /**
     * Search employees
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $userID = Auth::user()->id;
        $id = $request['EmployeeID'];
        $office = $request['OfficeName'];
        $department = $request['DepartName'];
        $companyID = $request['CompanyName'];
        $listOffice = null;
        $listDepart = null;
        if($office != ''){
            $listOffice =  $this->officeRepository->getListOfficeByDoctor($companyID, $userID);
        }
        if($department != ''){
            $listDepart = $this->departmentRepository->getAllByOfficeID($office);
        }
        $employee = $this->employeeRepository->search($id, $office, $department, $companyID, $userID);
        $officeList = $this->officeRepository->getAll($companyID);
        $companyList = $this->companyRepository->getAllDoctorCompany($userID);
//         dd($companyList);
        //check if $id is an integer variable
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            $id = null;
        }
        //Handle data from employee
        if ($employee != null) {
            foreach ($employee as $employeeTmp) {
                $entryDay = str_split($employeeTmp->entry_ym, 4);
                if ($employeeTmp->entry_ym != '' && strlen($employeeTmp->entry_ym) >= 5) {
                    $employeeTmp->entry_ym = $entryDay[0] . "/" . $entryDay[1];
                }
                //Get all from department by department_id
                $departmentList = $this->departmentRepository->get($employeeTmp->department_id);
                //Set department->name into $employee named departmentName
                $employeeTmp->departmentName = $departmentList->name;
                //Set format Y/m/d for birth_date
                if($employeeTmp->birthdate != '' ){
                    $employeeTmp->birthdate = date('Y/m/d',strtotime($employeeTmp->birthdate));
                }
                //Set data for $employeeTmp->is_absence
                if ($employeeTmp->is_absence !== ''){
                    if ($employeeTmp->is_absence === true) {
                        $employeeTmp->is_absence = '休職';
                    } if ($employeeTmp->is_absence === false) {
                        $employeeTmp->is_absence = '通常';
                    }
                }
                
                //Set data for $employeeTmp->new_graduate_midway
                if ($employeeTmp->new_graduate_midway !== '') {
                    if ($employeeTmp->new_graduate_midway === true) {
                        $employeeTmp->new_graduate_midway = '新卒';
                    } if ($employeeTmp->new_graduate_midway === false) {
                        $employeeTmp->new_graduate_midway = '中途';
                    }
                }
                
                //Set data for $employeeTmp->is_retirement
                if ($employeeTmp->is_retirement !== '') {
                    if ($employeeTmp->is_retirement === true) {
                        $employeeTmp->is_retirement = '退職';
                    } if ($employeeTmp->is_retirement === false) {
                        $employeeTmp->is_retirement = '在職中';
                    }
                }
                
                //Set gender for employee
                if ($employeeTmp->gender !== '') {
                    if ($employeeTmp->gender == 'M') {
                        $employeeTmp->gender = '男性';
                    }
                    else if ($employeeTmp->gender == 'F') {
                        $employeeTmp->gender = '女性';
                    }else{
                        $employeeTmp->gender = '';
                    }
                }
            }
        }
        $request->flash();
        return view('doctor.employee')->with('employee', $employee)->with('office', $officeList)->with('company', $companyList)
        ->with('listOffice', $listOffice)->with('listDepart', $listDepart);
    }

    /*
    |--------------------------------------------------------------------------
    | Stress List
    |--------------------------------------------------------------------------
    */

    /**
     * check role user
     *
     * @return \Illuminate\Http\Response
     */
    public function checkRole($role)
    {
        return Auth::user()->authorizeRoles($role);
    }


    /**
     * Display a list stress employee
     *
     */
    public function stress_index()
    {
        $this->checkRole(User::COMPANY);
        $company_id = Auth::user()->company_id;
        $office_id = Auth::user()->office_id;
        $department_id = Auth::user()->department_id;
        $departments = [];
        if ($office_id != 0) {
            $office = $this->officeRepository->get($office_id);
            if ($department_id != 0) {
                $departments = $this->departmentRepository->get($department_id);
            } else {
                $departments = $this->departmentRepository->getAllByOfficeID($office_id)->toArray();
            }
        } else {
            $office = $this->officeRepository->getListOffices($company_id)->toArray();
        }
        $employees = $this->employeeRepository->stress_index($company_id
            , ($office_id == 0 ? null : $office_id), ($department_id == 0 ? null : $department_id));
        return view('company.stress-list')->with('listOffices', $office)->with('listDepartments', $departments)
            ->with('employees', $employees);
    }

    /**
     * Search employee with office, department, employee_id
     *
     */
    public function stress_search(Request $request)
    {
        $this->checkRole(User::COMPANY);
        $role_office = Auth::user()->office_id;
        $role_department = Auth::user()->department_id;
        if($request['Action'] === 'Count' && $role_office === 0 && $role_department === 0){
            //Code count point
            $period = str_replace("/","",$request['SearchMonth']);

            $this->calculationRepository->deleteByPeriod($period);

            $this->calculationRepository->insertBloodPressurePoint($period, Auth::user()->company_id);

            $this->calculationRepository->insertAbsentPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertLateArrivalPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertOTPerMonthPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertMondayAbsentPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertMondayLatePoint($period, Auth::user()->company_id);

            $this->calculationRepository->insertStressCheckPoint($period, Auth::user()->company_id);

            $this->calculationRepository->insertIndustryPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertAdjustmentPoint($period, Auth::user()->company_id);
            $this->calculationRepository->insertNumberOfEmployeePoint($period, Auth::user()->company_id);

            $this->calculationRepository->deleteZeroPoints();


        }
        $validation = Validator::make($request->all(), [
            'OfficeName' => 'nullable',
            'DepartmentName' => 'nullable',
            'EmployeeID' => 'nullable',
            'SearchMonth' => 'required|date_format:Y/m|before_or_equal:'.date("Y/m", strtotime(Carbon::now()))
        ]);
        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }
        $company_id = Auth::user()->company_id;
        $office_id = Auth::user()->office_id;
        $department_id = Auth::user()->department_id;
        $departments = [];
        if ($office_id != 0) {
            $office = $this->officeRepository->get($office_id);
            if ($department_id != 0) {
                $departments = $this->departmentRepository->get($department_id);
            } else {
                $departments = $this->departmentRepository->getAllByOfficeID($office_id)->toArray();
            }
        } else {
            $office = $this->officeRepository->getListOffices($company_id)->toArray();
            if($request['OfficeName'] != null || $request['OfficeName'] != ""){
                $departments = $this->departmentRepository->getAllByOfficeID($request['OfficeName'])->toArray();
            }
        }
        $employees = $this->employeeRepository->stress_search($company_id,
            ($office_id == 0 ? $request['OfficeName'] : $office_id),
            ($department_id == 0 ? $request['DepartmentName'] : $department_id),
            $request['EmployeeID'],str_replace("/","",$request['SearchMonth']), $request['Column'], $request['Order']);
        $request->flash();
        $request->session()->put('stress-search', $request->all());
        return view('company.stress-list')->with('employees', $employees)
            ->with('listOffices', $office)->with('listDepartments', $departments);
    }

    /**
     * Export stress list
     *
     */
    public function stress_export(Request $request)
    {
        $this->checkRole(User::COMPANY);
        $employees = $this->employeeRepository->stress_export(Auth::user()->company_id, $request['idOffice'], $request['idDepartment'], $request['idEmployee'],($request['idSearchMonth'] != null ? str_replace("/","",$request['SearchMonth']) : date("Ym", strtotime(Carbon::now()))));
        $sheetArray[] = array('社員ID', '部署名', '欠勤ポイント', '遅刻ポイント', '残業時間ポイント'
        , '血圧ポイント', 'ストレスチェック判定ポイント', '当月ストレス判定ポイント', '3か月前ストレス判定ポイント','半年前ストレス判定ポイント');
        foreach ($employees as $row) {
            $sheetArray[] = array($row->code, $row->de_name, $row->ap, $row->lp,
                $row->op, $row->bp,0, $row->cmonth, $row->tmonth, $row->smonth);
        }
        $newDate = date("Ym", strtotime(Carbon::now()));
        Excel::create('StressList-' . $newDate, function ($excel) use ($sheetArray) {
            $excel->sheet('ExportFile', function ($sheet) use ($sheetArray) {
                $sheet->fromArray($sheetArray, null, 'A1', false, false);
            });

        })->export('csv');
    }

    public function stress_import(Request $request)
    {
        $uploadedAttendanceFileId = -1;
        $uploadedHealthCheckFileId = -1;
        $uploadedStressCheckFileId = -1;

        $listEmployee = $this->employeeRepository->getForImport(Auth::user()->company_id,Auth::user()->office_id,Auth::user()->department_id)->toArray();
        $attendanceFile = $request['attendanceFile'];
        if (!is_string($attendanceFile)) {
            if (!checkUploadFileName($attendanceFile->getClientOriginalName())) {
                return response()->json([
                    'result' => false,
                    'message' => '適切なファイルをインポートしてください。例えば　Attendance_201701.csv'
                ]);
            }
            $currentAttendance = $this->attendanceRepository->getAll(Auth::user()->company_id,Auth::user()->office_id,Auth::user()->department_id)->toArray();
            $uploadedAttendanceFileId = $this->attendanceRepository->saveAttendanceFromFile($attendanceFile,$listEmployee, $currentAttendance);
            if (gettype($uploadedAttendanceFileId) === 'string') {
                return response()->json([
                    'result' => false,
                    'message' => $uploadedAttendanceFileId,
                    'file' => 'Attendance File'
                ]);
            }
        }


        $healthCheckFile = $request['healthCheckFile'];
        if (!is_string($healthCheckFile)) {
            if (!checkUploadFileName($healthCheckFile->getClientOriginalName())) {
                return response()->json([
                    'result' => false,
                    'message' => '適切なファイルをインポートしてください。例えば　HealthCheck_201701.csv'
                ]);
            }
            $currenthealthCheck = $this->healthCheckRepository->getAll(Auth::user()->company_id,Auth::user()->office_id,Auth::user()->department_id)->toArray();
            $uploadedHealthCheckFileId =  $this->healthCheckRepository->saveHealthCheckFromFile($healthCheckFile,$listEmployee,$currenthealthCheck);
            if (gettype($uploadedHealthCheckFileId) === 'string') {
                return response()->json([
                    'result' => false,
                    'message' => $uploadedHealthCheckFileId,
                    'file' => 'Health Check File'
                ]);
            }
        }


        $stressCheckFile = $request['stressCheckFile'];
        if (!is_string($stressCheckFile)) {
            if (!checkUploadFileName($stressCheckFile->getClientOriginalName())) {
                return response()->json([
                    'result' => false,
                    'message' => '適切なファイルをインポートしてください。例えば　StressCheck_201701.csv'
                ]);
            }
            $currentStressCheck = $this->stressCheckRepository->getAll(Auth::user()->company_id,Auth::user()->office_id,Auth::user()->department_id)->toArray();
            $uploadedStressCheckFileId = $this->stressCheckRepository->saveStressCheckFromFile($stressCheckFile,$listEmployee,$currentStressCheck);
            if (gettype($uploadedStressCheckFileId) === 'string') {
                return response()->json([
                    'result' => false,
                    'message' => $uploadedStressCheckFileId,
                    'file' => 'Stress Check File'
                ]);
            }
        }

//
//        $totalFileId = max($uploadedHealthCheckFileId, $uploadedAttendanceFileId, $uploadedStressCheckFileId);
//        $uh = UploadHistory::where('id', '=', $totalFileId)->first();
//        $this->calculationRepository->deleteByPeriod($uh['period']);
//
//        if ($uploadedHealthCheckFileId != -1){
//            $this->calculationRepository->insertBloodPressurePoint($uploadedHealthCheckFileId);
//        }
//
//        if ($uploadedAttendanceFileId != -1){
//            $this->calculationRepository->insertAbsentPoint($uploadedAttendanceFileId);
//            $this->calculationRepository->insertLateArrivalPoint($uploadedAttendanceFileId);
//            $this->calculationRepository->insertOTPerMonthPoint($uploadedAttendanceFileId);
//            $this->calculationRepository->insertMondayAbsentPoint($uploadedAttendanceFileId);
//            $this->calculationRepository->insertMondayLatePoint($uploadedAttendanceFileId);
//        }
//
//        if ($uploadedStressCheckFileId != -1){
//            $this->calculationRepository->insertStressCheckPoint($uploadedStressCheckFileId);
//        }
//
//
//        $this->calculationRepository->insertIndustryPoint($uh['period']);
//
//        $this->calculationRepository->insertAdjustmentPoint($uh['period']);
//
//        $this->calculationRepository->insertNumberOfEmployeePoint($uh['period']);






        return response()->json([
            'result' => true
        ]);
    }

    /**
     * Get data for comment list
     *
     * @return \Illuminate\Http\Response
     */
    public function stress_comment($id)
    {
        $employee = $this->employeeRepository->get($id);
        $listComment = $this->industrialPhysicianCommentHistoryRepository->getCommentList($id);
        foreach ($listComment as $list){
            if($list->interview_date != ''){
                $list->interview_date = date('Y/m/d',strtotime($list->interview_date));
            }
        }
        return view('company.commentList')->with('listComment', $listComment)->with('empCode', $employee->code);
    }

    /**
     * Import list employee
     *
     * @return \Illuminate\Http\Response
     */
    public function employee_import(Request $request)
    {
        $this->checkRole(User::COMPANY);
        $employeeFile = $request['csvFile'];
        if (!is_string($employeeFile)) {
            if (!checkUploadFileName($employeeFile->getClientOriginalName())) {
                return response()->json([
                    'result' => false,
                    'message' => '適切なファイルをインポートしてください。例えば　Employee_201701.csv'
                ]);
            }
            $company_code = $this->companyRepository->get(Auth::user()->company_id)->code;
            $office_code = null;
            $department_code = null;
            if(Auth::user()->office_id != 0){
                $office_code = $this->officeRepository->get(Auth::user()->office_id)->code;
            }
            if(Auth::user()->department_id != 0){
                $department_code = $this->departmentRepository->get(Auth::user()->department_id)->code;
            }
            $listDepartment = $this->departmentRepository->getAllByCompanyID(Auth::user()->company_id)->toArray();
            $result = $this->employeeRepository->importFile($employeeFile, $company_code, $office_code, $department_code, $listDepartment);
            if ($result == null) {
                return response()->json([
                    'result' => true
                ]);
            } else {
                return response()->json([
                    'result' => false,
                    'message' => $result
                ]);
            }
        }
        return response()->json([
            'result' => false,
            'message' => '適切なファイルをインポートしてください。例えば　Employee_201701.csv'
        ]);
    }

    /**
     * Export stress list
     *
     */
    public function employee_export()
    {
        $this->checkRole(User::COMPANY);
        $employees = $this->employeeRepository->exportFile(Auth::user()->company_id, Auth::user()->office_id, Auth::user()->department_id);
        $sheetArray[] = array('company_code', 'office_code','department_code','employee_code', 'birthdate', 'position', 'new_graduate_midway'
        , 'is_absence', 'is_retirement', 'entry_ym', 'gender');
        foreach ($employees as $row) {
            $sheetArray[] = array($row->co, $row->of, $row->de, $row->code, date(Config::get('constants.date_format'), strtotime($row->birthdate)), $row->position,
                ($row->new_graduate_midway == 0 ? "0" : 1), ($row->is_absence == 0 ? "0" : 1), ($row->is_retirement == 0 ? "0" : 1), $row->entry_ym, $row->gender);
        }
        $newDate = date("Ym", strtotime(Carbon::now()));
        Excel::create('Employee-' . $newDate, function ($excel) use ($sheetArray) {
            $excel->sheet('ExportFile', function ($sheet) use ($sheetArray) {
                $sheet->fromArray($sheetArray, null, 'A1', true, false);
            });

        })->export('csv');
    }

}
