<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/22/17
 * Time: 11:13 AM
 */

namespace MentalHealthAI\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use MentalHealthAI\Models\Department;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IDepartmentRepository;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use MentalHealthAI\User;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Psy\Util\Json;
use Alert;


class DepartmentController extends Controller
{
    protected $departmentRepository;
    protected $companyRepository;
    protected $officeRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(IDepartmentRepository $departmentRepository,
                                ICompanyRepository $companyRepository,
                                IOfficeRepository $officeRepository)
    {
        $this->departmentRepository = $departmentRepository;
        $this->officeRepository = $officeRepository;
        $this->companyRepository = $companyRepository;
        $this->middleware('auth');
    }

    public function checkRole($role)
    {
        return Auth::user()->authorizeRoles($role);
    }


    /*
    |--------------------------------------------------------------------------
    | Role: Company
    |--------------------------------------------------------------------------
    */
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(Request $request)
    {
        Validator::make($request->all(), [
            'department_name' => 'required|string|max:255',
            'contact_phone_number' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255'
        ])->validate();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($o)
    {
        $this->authenticate();
        $departmentId = Auth::user()->department_id;
        if ($departmentId != 0) {
            $departments = $this->departmentRepository->getAllByDepartmentID($departmentId);
        } else {
            $departments = $this->departmentRepository->getAll($o);
        }

        $office = $this->officeRepository->get($o);
        $company = $this->companyRepository->get($office->company_id);
        return view('company.department.department-list')->with('departments', $departments)
            ->with('office', $office)->with('company', $company)->with('listDepartments', $departments);
    }

    /**
     * Search Departments
     *
     * @return \Illuminate\Http\Response
     */
    public function search($o, Request $request)
    {
        $this->authenticate();
        $id = $request['DepartmentId'];
        $name = $request['DepartmentName'];
        $office = $this->officeRepository->get($o);
        $company = $this->companyRepository->get($office->company_id);
        $departments = $this->departmentRepository->search($o, $id, $name);
        $listDepartments = $this->departmentRepository->getAllByOfficeID($o);
        $request->flash();
        return view('company.department.department-list')->with('departments', $departments)
            ->with('office', $office)->with('company', $company)->with('listDepartments', $listDepartments);
    }

    /**
     * Import list department
     *
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $this->authenticate();
        $office_code = $this->officeRepository->get($request['officeID'])->code;
        if ($request->file('departmentFile')) {
            $path = $request->file('departmentFile')->getRealPath();
            $data = Excel::load($path, function ($reader) {
                $reader->noHeading();
            })->get();
            $rowNumber = 1;
            try {
                DB::beginTransaction();
                if (!empty($data) && $data->count()) {
                    $dataArray = $data->toArray();
                    foreach ($dataArray as $row) {
                        if (!empty($row)) {
                            $rowNumber++;

                            if (array_search($row, $dataArray) == 0) {
                                continue;
                            }//Skip header
                            if($office_code != $row[0]){
                                throw new Exception();
                            }
                            $admin = new Department();
                            $admin['office_id'] = $row[0];
                            $admin['code'] = $row[1];
                            $admin['name'] = $row[2];
                            $admin['point_factor'] = $row[3];
                            $admin['contact_phone_number'] = $row[4];
                            $admin['contact_name'] = $row[5];

                            $this->departmentRepository->import($admin);
                        }
                    }
                }
                DB::commit();
            } catch (QueryException $exception) {
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => 'csvの●行目に不正な値が入力されています'
                ]);
            } catch (Exception $e){
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => 'Error office code!'
                ]);
            }

        }
        return response()->json([
            'result' => true,
            'message' => 'csvのインポートに成功しました'
        ]);
    }

    /**
     * Export list department
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $this->authenticate();
        $items = $this->departmentRepository->getAllByOfficeID($request['officeID']);
        $sheetArray[] = array('office_code','department_code','department_name','point_factor','contact_phone_number','contact_name');
        $office_code = $this->officeRepository->get($request['officeID'])->code;
        foreach ($items as $row) {
            $sheetArray[] = array($office_code, $row->code, $row->name, $row->point_factor,
                $row->contact_phone_number, $row->contact_name);
        }
        $newDate = date("Ym", strtotime(Carbon::now()));
        Excel::create('Department-' . $newDate, function ($excel) use ($sheetArray) {
            $excel->sheet('ExportFile', function ($sheet) use ($sheetArray) {
                $sheet->fromArray($sheetArray, null, 'A1', false, false);
            });
        })->export('csv');
    }

    /**
     * Get view edit department
     *
     * @return \Illuminate\Http\Response
     */
    public function getEdit($q)
    {
        $this->authenticate();
        $department = $this->departmentRepository->get($q);
        $office = $this->officeRepository->get($department->office_id);
        $company = $this->companyRepository->get($office->company_id);
        return view('company.department.department-edit')->with('department', $department)
            ->with('office', $office)->with('company', $company);
    }


    /**
     * Do edit department
     *
     * @return \Illuminate\Http\Response
     */
    public function doEdit(Request $request)
    {
        $this->authenticate();
        $this->validator($request);

        $department = $this->departmentRepository->get($request['department_id']);
        $office = $this->officeRepository->get($department->office_id);
        $company = $this->companyRepository->get($office->company_id);
        if (!is_null($department)) {
            $department['name'] = $request['department_name'];
            $department['contact_phone_number'] = $request['contact_phone_number'];
            $department['contact_name'] = $request['person_in_charge'];
            $department->save();
            alert()->success('', '変更しました。');
//            alert()->message('aa')->confirmButton('OK')->cancelButton('Cancel');
        } else {
            alert()->error('', 'エラーが発生したので、変更されませんでした。');
        }
        return redirect(url('/company/department/' . $office['id']));

//        return view('company.department.department-edit')->with('department', $department)
//            ->with('office',$office)->with('company',$company);
    }

    private function authenticate()
    {
        $this->middleware('auth');
        $this->middleware('roles:' . User::COMPANY);
    }

    /**
     * @param $q
     * @return $this
     */
    public function signup($q)
    {

        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office['company_id']);

        return view('company.department.new-department')
            ->with('department_id', $q)
            ->with('company', $company)
            ->with('office', $office);
    }


    /**
     *
     * @param Request $request
     */
    public function doSignup(Request $request)
    {

        $department = new Department();
        $department['office_id'] = $request['txtOfficeId'];
        $department['name'] = $request['txtName'];
        $department['point_factor'] = 100;
        $department['contact_phone_number'] = $request['txtContactPhoneNumber'];
        $department['contact_name'] = $request['txtContactName'];
        $department['is_active'] = true;
        $input = Input::get('btnSave');
        $newDepartment = null;
        if (isset($input)) {
            $newDepartment = $this->departmentRepository->save($department);
            if (!isset($newDepartment)) {
                return response()->json([
                    'result' => false,
                    'message' => '登録が成功しません。'
                ]);
            }
            return response()->json([
                'result' => true,
                'message' => '登録が成功しました。',
                'url' => url('/company/employee/signup/' . $newDepartment->id)
            ]);
        } else {
            $newDepartment = $this->departmentRepository->save($department);
            if (!isset($newDepartment)) {
                return response()->json([
                    'result' => false,
                    'message' => '登録が成功しません。'
                ]);
            }
            return response()->json([
                'result' => true,
                'message' => '登録が成功しました。',
                'url' => url('/company/department/' . $request['txtOfficeId'])
            ]);
        }
    }


    /**
     * add department from employee new
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \MentalHealthAI\Department $department
     * @return \Illuminate\Http\Response
     */
    public function addDepartment(Request $request)
    {

        $department = new Department([
            'office_id' => $request['officeId'],
            'name' => $request['nameDepartment'],
            'point_factor' => 1,
            'is_active' => true
        ]);
        $newDepartment = $this->departmentRepository->save($department);
        return Json::encode($newDepartment);

    }

    /*
    |--------------------------------------------------------------------------
    | Role: Admin
    |--------------------------------------------------------------------------
    */

    public function index_admin($o)
    {
        $departments = $this->departmentRepository->getAll($o);
        $office = $this->officeRepository->get($o);
        $company = $this->companyRepository->get($office->company_id);
        return view('admin.department.department-list')->with('departments', $departments)
            ->with('office', $office)->with('company', $company)->with('listDepartments', $departments);
    }

    /**
     * Search Departments
     *
     * @return \Illuminate\Http\Response
     */
    public function search_admin($o, Request $request)
    {
        $id = $request['DepartmentId'];
        $name = $request['DepartmentName'];
        $departments = $this->departmentRepository->search($o, $id, $name);
        $office = $this->officeRepository->get($o);
        $company = $this->companyRepository->get($office->company_id);
        $listDepartments = $this->departmentRepository->getAllByOfficeID($o);
        $request->flash();
        return view('admin.department.department-list')->with('departments', $departments)
            ->with('office', $office)->with('company', $company)->with('listDepartments', $listDepartments);
    }

    /**
     * Update point factor office
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \MentalHealthAI\Department $department
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->checkRole(User::ADMIN);
        $id = $request['pk'];
        $department = new Department([
            'point_factor' => $request['value']
        ]);
        $result = $this->departmentRepository->update($id, $department);
        return $result;
    }

    /**
     * Get view edit department
     *
     * @return \Illuminate\Http\Response
     */
    public function admin_getEdit($q)
    {
        $this->authenticate();
        $department = $this->departmentRepository->get($q);
        $office = $this->officeRepository->get($department->office_id);
        $company = $this->companyRepository->get($office->company_id);
        return view('admin.department.department-edit')->with('department', $department)
            ->with('office', $office)->with('company', $company);
    }

    public function admin_doEdit(Request $request)
    {
        $this->authenticate();
        $this->validator($request);

        $department = $this->departmentRepository->get($request['department_id']);
        $office = $this->officeRepository->get($department->office_id);
        $company = $this->companyRepository->get($office->company_id);
        if (!is_null($department)) {
            $department['name'] = $request['department_name'];
            $department['contact_phone_number'] = $request['contact_phone_number'];
            $department['contact_name'] = $request['person_in_charge'];
            $department->save();
            alert()->success('変更しました');
//            alert()->message('aa')->confirmButton('OK')->cancelButton('Cancel');
        } else {
            alert()->error('エラーが発生したので、変更されませんでした。');
        }
        return redirect(url('/admin/department/' . $office['id']));

//        return view('company.department.department-edit')->with('department', $department)
//            ->with('office',$office)->with('company',$company);
    }

    /**
     * @param $q
     * @return $this
     */
    public function admin_signup($q)
    {

        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office['company_id']);

        return view('admin.department.new-department')
            ->with('company', $company)->with('office', $office);
    }

    /**
     *
     * @param Request $request
     */
    public function admin_doSignup(Request $request)
    {

        $department = new Department();
        $department['office_id'] = $request['txtOfficeId'];
        $department['name'] = $request['txtName'];
        $department['point_factor'] = 100;
        $department['contact_phone_number'] = $request['txtContactPhoneNumber'];
        $department['contact_name'] = $request['txtContactName'];
        $department['is_active'] = true;
        $newDepartment = $this->departmentRepository->save($department);
        if (isset($newDepartment)) {
            alert()->success('', '登録が成功しました。');
        }else{
            alert()->error('', 'error!');
        }
        return redirect(url('/admin/department/' . $department['office_id']));

    }
}