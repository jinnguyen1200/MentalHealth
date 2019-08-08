<?php

namespace MentalHealthAI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use MentalHealthAI\Jobs\SendEmail;
use MentalHealthAI\Models\SystemAdminInformation;
use MentalHealthAI\Models\SystemAdministrator;
use MentalHealthAI\Repositories\Interfaces\ISystemAdministratorRepository;
use Carbon\Carbon;
use MentalHealthAI\User;

class HomeController extends Controller
{
    protected $systemAdminInformationRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ISystemAdministratorRepository $systemAdminInformationRepository)
    {
        $this->systemAdminInformationRepository = $systemAdminInformationRepository;
        $this->middleware('auth');
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request
            ->user()
            ->authorizeRoles([User::COMPANY, User::DOCTOR, User::ADMIN]);
        $admins = $this->systemAdminInformationRepository->getAll();;
        return view('home')->withAdmins($admins);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $admin = new SystemAdministrator();
        $admin['mail_address'] = $request['name'];
        $admin['password'] = $request['name'];
        $this->systemAdminInformationRepository -> save($admin);

        Log::info('Sending');
        $job = (new SendEmail())
            ->delay(Carbon::now()->addSeconds(10));
        $this -> dispatch($job);
        Log::info('Sent');

        $admins = $this->systemAdminInformationRepository->getAll();;
        return view('home')->withAdmins($admins);
    }
}