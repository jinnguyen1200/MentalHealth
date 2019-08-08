<?php

namespace MentalHealthAI\Http\Controllers\Auth;

use Illuminate\Support\Facades\Session;
use MentalHealthAI\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use MentalHealthAI\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
//    protected $redirectTo = '/home';


    protected function authenticated()
    {
        Session::put('role',Auth::user()->account_type);
        if ( Auth::user()->hasAnyRole([User::DOCTOR])){
            return redirect('/doctor/employee');
        }else if ( Auth::user()->hasAnyRole([User::COMPANY])){
            return redirect('/company/office/');
        }else {
           return redirect('/admin/company/');
        }

    }

    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password', 'account_type', 'is_active');
    }



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
