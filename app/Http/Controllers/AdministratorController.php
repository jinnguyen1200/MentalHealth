<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/28/17
 * Time: 3:56 PM
 */

namespace MentalHealthAI\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Jobs\SendEmail;
use MentalHealthAI\Models\Administrator;
use MentalHealthAI\Models\UserCompany;
use MentalHealthAI\Repositories\Interfaces\IAdministratorRepository;
use MentalHealthAI\Repositories\Interfaces\ICompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IDepartmentRepository;
use MentalHealthAI\Repositories\Interfaces\IOfficeRepository;
use MentalHealthAI\Repositories\Interfaces\IUserCompanyRepository;
use MentalHealthAI\Repositories\Interfaces\IUserOfficeRepository;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Auth;

class AdministratorController extends Controller
{
    protected $adminRepository;
    protected $companyRepository;
    protected $officeRepository;
    protected $departmentRepository;
    protected $userCompanyRepository;
    protected $userOfficeRepository;


    public function __construct(IAdministratorRepository $adminRepository,
                                ICompanyRepository $companyRepository,
                                IOfficeRepository $officeRepository,
                                IDepartmentRepository $departmentRepository,
                                IUserCompanyRepository $userCompanyRepository,
                                IUserOfficeRepository $userOfficeRepository)
    {
        $this->adminRepository = $adminRepository;
        $this->companyRepository = $companyRepository;
        $this->officeRepository = $officeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->userCompanyRepository = $userCompanyRepository;
        $this->userOfficeRepository = $userOfficeRepository;
        $this->middleware('auth');
    }

    /**
     * Display a account list of physician
     *
     * @return \Illuminate\Http\Response
     */
    public function physicianAccountList($q)
    {
        $accounts = $this->adminRepository->getPhysicianAccountByCompany($q);
        $company = $this->companyRepository->get($q);


        return view('admin.physician-account-list')->with('accounts', $accounts)->with('company', $company);
    }

    /**
     * Display a account list of physician
     *
     * @return \Illuminate\Http\Response
     */
    public function officePhysicianAccountList($q)
    {
        $accounts = $this->adminRepository->getPhysicianAccountByOffice($q);
        $office = $this->officeRepository->get($q);


        return view('admin.office.physician-account-list')->with('accounts', $accounts)->with('office', $office);
    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function companyAccountList($q)
    {
        $accounts = $this->adminRepository->getAllByCompanyId($q);
        $company = $this->companyRepository->get($q);

        return view('admin.company.company-account-list')->with('accounts', $accounts)->with('company', $company);
    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function adminOfficeAccountList($q)
    {
        $accounts = $this->adminRepository->getAllByOfficeIdAndRole($q, Config::get('constants.roles_company'));
        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office['company_id']);

        return view('admin.office.office-account-list')
            ->with('accounts', $accounts)
            ->with('office', $office)
            ->with('company', $company);
    }

    /**
     * Display a list of administrator according to logged on User role (Company)
     *
     * @return \Illuminate\Http\Response
     */
    public function companyOfficeAccountList($q)
    {
        $accounts = $this->adminRepository->getAllByOfficeIdAndRole($q, Config::get('constants.roles_company'));
        $office = $this->officeRepository->get($q);
        $company = $this->companyRepository->get($office['company_id']);

        return view('company.office.office-account-list')
            ->with('accounts', $accounts)
            ->with('office', $office)
            ->with('company', $company);
    }

    /**
     * Display a list of administrator according to logged on User role (Company)
     *
     * @return \Illuminate\Http\Response
     */
    public function departmentAccountList($q)
    {
        $accounts = $this->adminRepository->getAllByDepartmentIdAndRole($q, Config::get('constants.roles_company'));
        $department = $this->departmentRepository->get($q);
        $office = $this->officeRepository->get($department['office_id']);
        $company = $this->companyRepository->get($office['company_id']);

        $accountType = Auth::user()->account_type;
        if ($accountType == 1) {
            return view('admin.department.department-account-list')
                ->with('accounts', $accounts)
                ->with('department', $department)
                ->with('office', $office)
                ->with('company', $company);
        } else {
            return view('company.department.department-account-list')
                ->with('accounts', $accounts)
                ->with('department', $department)
                ->with('office', $office)
                ->with('company', $company);
        }
    }

    /**
     * new account physician of company
     *
     * @return \Illuminate\Http\Response
     */
    public function newPhysicianAccount(Request $request)
    {
        $this->validateNewAdminstrator($request);

        $account = $this->adminRepository->getByEmail($request['txtNewAccount']);
        if (!$account) {
            $account = new Administrator();
            $account['account_type'] = 3;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = 0;
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
        }
        return $this->adminRepository->saveWithCompany($account, $request['companyId']);
    }

    /**
     * new account physician of office
     *
     * @return \Illuminate\Http\Response
     */
    public function newOfficePhysicianAccount(Request $request)
    {
        $this->validateNewAdminstrator($request);
        $office = $this->officeRepository->get($request['officeId']);

        $account = $this->adminRepository->getByEmail($request['txtNewAccount']);
        if (!$account) {
            $account = new Administrator();
            $account['account_type'] = 3;
            $account['company_id'] = $office->company_id;
            $account['office_id'] = $request['officeId'];;
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
        }
        return $this->adminRepository->saveWithOffice($account, $request['officeId']);
    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function newCompanyAccount(Request $request)
    {
        $this->validateNewAdminstrator($request);

        $account = $this->adminRepository->getByEmail($request['txtNewAccount']);
        if (!$account) {
            $account = new Administrator();
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = 0;
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
        } else {
            if ($account['is_active'] == 1) {
                return response()->json([
                    'result' => false,
                    'message' => '電子メールアドレスはすでに使用中です。'
                ]);
            }
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = 0;
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
            $account['is_active'] = 1;
        }

        $this->adminRepository->save($account);
        return response()->json([
            'result' => true,
            'message' => '登録が完了しました。'
        ]);

    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function newOfficeAccount(Request $request)
    {
        $this->validateNewAdminstrator($request);

        $account = $this->adminRepository->getByEmail($request['txtNewAccount']);
        if (!$account) {
            $account = new Administrator();
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = $request['officeId'];
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
        } else {
            if ($account['is_active'] == 1) {
                return response()->json([
                    'result' => false,
                    'message' => '電子メールアドレスはすでに使用中です'
                ]);
            }
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = $request['officeId'];
            $account['department_id'] = 0;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
            $account['is_active'] = 1;
        }

        $this->adminRepository->save($account);
        return response()->json([
            'result' => true,
            'message' => '登録が完了しました'
        ]);


    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function newDepartmentAccount(Request $request)
    {
        $this->validateNewAdminstrator($request);

        $account = $this->adminRepository->getByEmail($request['txtNewAccount']);
        if (!$account) {
            $account = new Administrator();
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = $request['officeId'];
            $account['department_id'] = $request['departmentId'];;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
        } else {
            if ($account['is_active'] == 1) {
                return response()->json([
                    'result' => false,
                    'message' => '電子メールアドレスはすでに使用中です'
                ]);
            }
            $account['account_type'] = 2;
            $account['company_id'] = $request['companyId'];
            $account['office_id'] = $request['officeId'];
            $account['department_id'] = $request['departmentId'];;
            $account['email'] = $request['txtNewAccount'];
            $password = $request['txtNewPassword'];
            $account['password'] = bcrypt($password);
            $account['is_active'] = 1;
        }

        $this->adminRepository->save($account);
        return response()->json([
            'result' => true,
            'message' => '登録が完了しました'
        ]);
    }

    function makePassword($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteAccount(Request $request)
    {
//        $accounts = $this->adminRepository->getAllByCompanyId($q);
        return $this->adminRepository->delete($request['accountId']);
//        return view('company-account-list')->with('accounts', $accounts);
    }

    public function deletePhysicianAccount(Request $request)
    {
        return $this->userCompanyRepository->deleteByUserAndCompany($request['accountId'], $request['companyId']);
    }

    public function deleteOfficePhysicianAccount(Request $request)
    {
        return $this->userOfficeRepository->deleteByUserAndOffice($request['accountId'], $request['officeId']);
    }

    /**
     * Display a list of company
     *
     * @return \Illuminate\Http\Response
     */
    public function preEditAccount(Request $request)
    {
        return $this->adminRepository->get($request['accountId']);
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function editAccount(Request $request)
    {
        $this->validator($request);
        $admin = $this->adminRepository->get($request['txtEditAccountId']);
        $admin['email'] = $request['txtEditAccount'];
        return $this->adminRepository->save($admin);
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function editPassword(Request $request)
    {
        $this->validatePassword($request);
        $admin = $this->adminRepository->get($request['txtEditAccountId']);
        $password = $request['txtEditPassword'];
        $admin['password'] = bcrypt($password);
        return $this->adminRepository->save($admin);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(Request $request)
    {
        $messages = [
            "txtEditAccount.required" => "メールアドレスを入力してください。",
            "txtEditAccount.email" => "メールアドレスの形式を入力してください。"

        ];
        Validator::make($request->all(), [
            'txtEditAccount' => 'required|email|string|max:255',
        ], $messages)->validate();
    }

    protected function validatePassword(Request $request)
    {
        Validator::make($request->all(), [
            'txtEditPassword' => 'required|string|min:6|max:255',
        ])->validate();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateNewAdminstrator(Request $request)
    {
        $messages = [
            "txtNewAccount.required" => "メールアドレスを入力してください。",
            "txtNewAccount.email" => "メールアドレスの形式を入力してください。",

        ];
        Validator::make($request->all(), [
            'txtNewAccount' => 'required|email|string|max:255',
            'txtNewPassword' => 'required|string|max:255|min:6'
        ], $messages)->validate();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function recoverPassword(Request $request)
    {
        $account = $this->adminRepository->get($request['accountId']);

        if ($account != null) {
            try {
                $password = $this->makePassword();
                $account['password'] = bcrypt($password);
                $this->adminRepository->save($account);

                Log::info('Sending');
                $mailer = SendEmail::getBaseMailer();
                $mailer->addAddress($account['email'], 'Customer');
                $mailer->Subject = 'Recover password';
                $mailer->Body = $password;

//                $job = (new SendEmail($mailer))
//                    ->delay(Carbon::now()->addSeconds(10));
//                $this->dispatch($job);
                $mailer->send();
                Log::info('Sent');
                return response()->json([
                    'result' => true,
                    'message' => 'パスワード再発行が完了しました。'
                ]);
            } catch (Exception $exception) {
                Log::info($exception->errorMessage());
                return response()->json([
                    'result' => false,
                    'message' => 'パスワード再発行に失敗しました。'
                ]);
            }
        }
        return response()->json([
            'result' => false,
            'message' => 'パスワード再発行に失敗しました。'
        ]);

    }
}