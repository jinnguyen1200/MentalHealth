<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/25/17
 * Time: 3:55 PM
 */

namespace MentalHealthAI\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Models\Office;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Input;
use MentalHealthAI\User;

class OfficeController extends Controller
{
    protected $officeRepository;
    protected $companyRepository;


    /**
     * Create a new controller instance.
     *
     * @param IOfficeRepository $officeRepository
     */
    public function __construct(IOfficeRepository $officeRepository, ICompanyRepository $companyRepository)
    {
        $this->officeRepository = $officeRepository;
        $this->companyRepository = $companyRepository;
        $this->middleware('auth');
        $this->middleware('roles:'.User::ADMIN.','.User::COMPANY);
    }


    public function checkRole($role){
        return Auth::user()->authorizeRoles($role);
    }


    public function checkSubRole($role){
        if(Auth::user()->department_id != 0){
            $currentRole = User::SUBDEPARTMENT;
        }else if(Auth::user()->office_id != 0){
            $currentRole = User::SUBOFFICE;
        }else{
            $currentRole = User::SUBCOMPANY;
        }
        if ($role == $currentRole) {
            return true;
        }
        abort(403, 'This action is unauthorized.');
    }

    /*
        |---------------------------------------------------------------------------------------------------------
        | Role: Company
        |---------------------------------------------------------------------------------------------------------
        */
    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->checkRole(User::COMPANY);
        $c = Auth::user()->company_id;
        $officeId = Auth::user()->office_id;
        $departmentId = Auth::user()->department_id;
        if ($officeId != 0 && $departmentId == 0){
            $offices = $this->officeRepository->getAllByOfficeId($officeId);
            $officesSelect = $this->officeRepository->getAllByOfficeIdWithoutPaginate($c);
        }else if ($officeId == 0 && $departmentId == 0){
            $offices = $this->officeRepository->getAll($c);
            $officesSelect = $this->officeRepository->getAllWithoutPaginate($c);
        }else{
            return redirect()->route('deparment-index', $officeId);
        }

        $company = $this->companyRepository->get($c);
        return view('company.office.office-list')->with('offices', $offices)
            ->with('company',$company)->with('listOffices', $officesSelect);
    }
    /**
     * Search offices
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $this->checkRole(User::COMPANY);
        $c = Auth::user()->company_id;
        $id = $request['OfficeId'];
        $name = $request['OfficeName'];
        $offices = $this->officeRepository->search($id, $name, $c);
        $company = $this->companyRepository->get($c);
        $listOffices = $this->officeRepository->getListOffices($c);
        $request->flash();
        return view('company.office.office-list')->with('offices', $offices)
            ->with('company',$company)->with('listOffices', $listOffices);
    }

    /**
     * Search offices
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function detail($q)
    {
        $this->checkRole(User::COMPANY);
        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office->company_id);
        return view('company.office.office-detail')->with('office', $office)->with('company', $company);
    }

    public function arrayPaginator($array, $request)
    {
        $page = Input::get('page', 1);
        $perPage = 10;
        $offset = ($page * $perPage) - $perPage;

        return new LengthAwarePaginator(array_slice($array, $offset, $perPage, true), count($array), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]);
    }

    /*
    |------------------------------------------------------------------------------------------------------------------------------------
    | Role: Admin
    |------------------------------------------------------------------------------------------------------------------------------------
    */

    /**
     * show offices in the company
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index_admin($c)
    {
        $this->checkRole(User::ADMIN);
        $offices = $this->officeRepository->getAll($c);
        $officesSelect = $this->officeRepository->getAllWithoutPaginate($c);
        $company = $this->companyRepository->get($c);
        return view('admin.office.office-list')->with('offices', $offices)
            ->with('company',$company)
            ->with('listOffices', $offices)
            ->with('officesSelect',$officesSelect);
    }

    /**
     * Get a validator for an incoming registration and edit request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(Request $request)
    {
        Validator::make($request->all(), [
            'office_name' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255',
            'point_factor' => 'nullable|integer|min:1|max:1000'
        ])->validate();
    }

    /**
     * Search offices
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function search_admin(Request $request,$c)
    {
        $this->checkRole(User::ADMIN);
        $id = $request['OfficeId'];
        $name = $request['OfficeName'];
        $offices = $this->officeRepository->search($id, $name, $c);
        $company = $this->companyRepository->get($c);
        $listOffices = $this->officeRepository->getListOffices($c);
        $officesSelect = $this->officeRepository->getAllWithoutPaginate($c);
        $request->flash();
        return view('admin.office.office-list')->with('offices', $offices)
            ->with('company',$company)
            ->with('listOffices', $listOffices)
            ->with('officesSelect',$officesSelect);
    }

    /**
     * Update point factor office
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \MentalHealthAI\Office $office
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->checkRole(User::ADMIN);
        $id = $request['pk'];
        $office = new Office([
            'point_factor' => $request['value']
        ]);
        $result = $this->officeRepository->update($id,$office);
        return $result;
    }

    /**
     * Show the form for sign up office.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($c)
    {
        $this->checkRole(User::ADMIN);
        $company = $this->companyRepository->get($c);
        return view('admin.office.office-create-edit')->with('company',$company);
    }

    /**
     * Create or Edit company.
     *
     * @param  \MentalHealthAI\Company $office
     * @return \Illuminate\Http\Response
     */
    public function doEdit(Request $request)
    {
        $this->checkRole(User::ADMIN);
        $this->validator($request);
        $office = new Office([
            'name' => $request['office_name'],
            'company_id' => $request['company_id'],
            'street_address' => $request['street_address'],
            'contact_phone_number' => $request['contact_phone_number'],
            'contact_name' => $request['person_in_charge'],
            'size' => 0
        ]);
        if($request['point_factor'] != null){
            $office['point_factor'] = $request['point_factor'];
        }else{
            $office['point_factor'] =100;
        }
        if (isset($request['btnEdit'])){
            $result = $this->officeRepository->update($request['office_id'],$office);
            if($result){
                alert()->success('', '変更しました。');
            }else{
                alert()->error('', 'Error!');
            }
            return redirect('/admin/office/'.$request['company_id']);
        }else if(isset($request['btnSignupWithOutSave'])){
            $result = $this->officeRepository->save($office);
            if($result){
                alert()->success('', '登録が完了しました。');
            }else{
                alert()->error('', 'Error!');
            }
            return redirect('/admin/office/'.$request['company_id']);
        }else if(isset($request['btnSignup'])){
            $result = $this->officeRepository->save($office);
            if ($result) {
                alert()->success('', '登録が成功しました。');
                return redirect(url('/admin/department/' . $result));
            }else{
                alert()->error('', 'Error!');
                return redirect('/admin/office/'.$request['company_id']);
            }
        }
    }


    /**
     * Show the form for editing the company.
     *
     * @param  \MentalHealthAI\Office $office
     * @return \Illuminate\Http\Response
     */
    public function getEdit($q)
    {
        $this->checkRole(User::ADMIN);
        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office->company_id);
        return view('admin.office.office-create-edit')->with('office',$office)->with('company',$company);
    }
}