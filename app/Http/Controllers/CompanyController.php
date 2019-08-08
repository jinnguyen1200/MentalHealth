<?php

namespace MentalHealthAI\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Models\Company;
use Illuminate\Http\Request;
use MentalHealthAI\Models\Industry;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use MentalHealthAI\User;
use UxWeb\SweetAlert\SweetAlert;

class CompanyController extends Controller
{
    protected $companyRepository;

    public function __construct(ICompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
        $this->middleware('auth');
        $this->middleware('roles:'.User::ADMIN);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companys = $this->companyRepository->getAll();
        $listCompanies = $this->companyRepository->getAllWithoutPagination();
        return view('admin.company.company-list')
            ->with('companys', $companys)
            ->with('listCompanys', $listCompanies);
    }

    /**
     * Search company
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $id = $request['CompanyID'];
        $name = $request['CompanyName'];
        $company = $this->companyRepository->search($id, $name);
        $request->flash();
        $listCompanies = $this->companyRepository->getAllWithoutPagination();
        return view('admin.company.company-list')
            ->with('companys', $company)
            ->with('listCompanys', $listCompanies);
    }

    /**
     * Show the form for sign up company.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listIndustry = Industry::all();
        return view('admin.company.company-create-edit')->with('listIndustry',$listIndustry);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
            'company_name' => 'required|string|max:255',
            'industry_id' => 'required|integer',
            'street_address' => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:255',
            'point_factor' => 'nullable|integer|min:1|max:1000',
        ])->validate();
    }

    /**
     * Create or Edit company.
     *
     * @param  \MentalHealthAI\Company $company
     * @return \Illuminate\Http\Response
     */
    public function doEdit(Request $request)
    {
        $this->validator($request);
        $company = new Company([
            'name' => $request['company_name'],
            'industry_id' => $request['industry_id'],
            'street_address' => $request['street_address'],
            'contact_phone_number' => $request['contact_phone_number'],
            'contact_name' => $request['person_in_charge'],
        ]);
        if($request['point_factor'] != null){
            $company['point_factor'] = $request['point_factor'];
        }else{
            $company['point_factor'] =100;
        }
        if (isset($request['btnEdit'])){
            $result = $this->companyRepository->update($company,$request['company_id']);
            if($result){
                alert()->success((isset($request['btnEdit']) ? '変更しました': '登録が完了しました'));
            }else{
                alert()->error('', 'Error!');
            }
            return redirect()->route('company');
        }else if(isset($request['btnSignupWithOutSave'])){
            $result = $this->companyRepository->save($company);
            if($result){
                alert()->success((isset($request['btnEdit']) ? '変更しました': '登録が完了しました'));
            }else{
                alert()->error('', 'Error!');
            }
            return redirect()->route('company');
        }else{
            $result = $this->companyRepository->save($company);
            if($result){
                alert()->success((isset($request['btnEdit']) ? '変更しました': '登録が完了しました'));
            }else{
                alert()->error('', 'Error!');
            }
            return redirect()->route('admin-office', [ 'c' => $result]);
        }

    }


    /**
     * Show the form for editing the company.
     *
     * @param  \MentalHealthAI\Company $company
     * @return \Illuminate\Http\Response
     */
    public function getEdit($q)
    {
        $company = $this->companyRepository->get($q);
        $listIndustry = Industry::all();
        return view('admin.company.company-create-edit')->with('company',$company)->with('listIndustry',$listIndustry);
    }
    /**
     * Update point factor company
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \MentalHealthAI\Company $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $id = $request['pk'];
        $company = new Company([
            'point_factor' => $request['value']
        ]);
        $result = $this->companyRepository->update($company,$id);
        return $result;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \MentalHealthAI\Company $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }


}
