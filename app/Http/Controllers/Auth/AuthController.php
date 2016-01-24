<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\PhotoController;
use App\User;
use Faker\Provider\Uuid;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Auth;
//use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use App\Jobs\LoginFailedEmail;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    protected $redirectPath = '/event';
    protected $redirectTo = '/event';

    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        parent::__construct();
        $this->middleware('guest', ['except' => ['getLogout', 'anyLogIn']]);
        $this->mailer = $mailer;
    }

    public function postLogin(Request $request)
    {
        $this->validate($request, [
            $this->loginUsername() => 'required', 'password' => 'required',
        ]);


        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->getCredentials($request);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttles);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        //send login failed email
        if ($email = $request->input('email')) {
            dispatch(new LoginFailedEmail($email));
        }

        if ($request->has('mobile')) {
            return [
                'login' => false,
                'errs' => 'no email and password record match'
            ];
        }

        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $this->getFailedLoginMessage(),
            ]);
    }

    public function authenticated(Request $request, $user)
    {

        if ($request->has('mobile')) {
            $user['X-CSRF-TOKEN'] = csrf_token();
            return compact('user');
        }
        return redirect('/event');

    }


//Auth::user()->update(['last_auth' => Carbon::now()]);

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    protected function validatorLoginName(array $data)
    {
        return Validator::make($data, [
            'login_name' => 'required'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create(array_merge([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password'])
        ], $this->newUserDefault()));
    }

    public function anyLogIn(Request $request)
    {
        $user = User::where(['login_name' => $request->input('login_name')])->first();
        if ($user) {
            Auth::login($user, true);
            $user['_token'] = csrf_token();
        }
        if ($request->mobile) {
            compact('user');
        }
        return redirect('/event');
    }

    public function getLoginViaLoginName(Request $request)
    {
        if ($request->input('login_name')) {
            return $this->postLoginViaLoginName($request);
        }
        return view('auth.loginViaLoginName');
    }

    public function postLoginViaLoginName(Request $request)
    {
        $user = User::where(['login_name' => $request->input('login_name')])->first();
        if ($user) {
            Auth::login($user, true);
        }
        $user = array_merge($user->toArray(), PhotoController::getUserPhotoGroupInfo());
        return compact('user');
    }

//REGISTER
    public function getRegisterLoginName(Request $request)
    {
        if ($login_name = $request->input('login_name')) {
            return $this->postRegisterLoginName($request, $login_name);
        }
        return view('auth.registerLoginName');
    }

    public function postRegisterLoginName(Request $request, $login_name = null)
    {
        $login_name = $login_name ?: $request->input('login_name');

        if ($login_name) {
            if (User::where('login_name', $login_name)->exists()) {
                return ['success' => false, 'reson' => 'this user registed'];
            }
        }

        $newUser = User::create($this->newUserDefault($login_name));
        if ($newUser) {
            Auth::login($newUser, true);
        }
        return compact('newUser');
    }

    public static function newUserDefault($login_name = null)
    {
        return [
            'login_name' => $login_name ? $login_name : Uuid::uuid(),
            'photo_last_group_id' => 1
        ];
    }

    public function postLoginName(Request $request)
    {
        return $this->getLoginName($request, $request->login_name);
    }

    public function getLoginName(Request $request, $login_name = null)
    {
        $login_name = $login_name ?: $request->login_name;
        if (!$login_name) {
            return view('photo.login');
        }
        $user = User::where('login_name', $login_name)->firstOrFail();
        Auth::login($user, true);

        if ($request->mobile) return $user;
        return redirect('/event');
    }

    public function getReg(Request $request)
    {
        return view('auth.registerLoginName');
    }
}
