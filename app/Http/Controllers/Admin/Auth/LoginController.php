<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\SessionKey;
use App\Enums\UserRole;
use App\Enums\ViewPaths\Admin\Auth;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Admin;
use App\Models\RemoteAccess;
use App\Models\LoginLogs;
use App\Services\AdminService;
use App\Traits\RecaptchaTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class LoginController extends BaseController
{
    use RecaptchaTrait;

    public function __construct(private readonly Admin $admin, private readonly AdminService $adminService)
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable
    {
        return $this->getLoginView(loginUrl: $type);
    }

    public function generateReCaptcha()
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (Session::has(SessionKey::ADMIN_RECAPTCHA_KEY)) {
            Session::forget(SessionKey::ADMIN_RECAPTCHA_KEY);
        }
        Session::put(SessionKey::ADMIN_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $recaptchaBuilder->output();
    }

    private function getLoginView(string $loginUrl): View
    {
        $loginTypes = [
            UserRole::ADMIN => getWebConfig(name: 'admin_login_url'),
            UserRole::EMPLOYEE => getWebConfig(name: 'employee_login_url')
        ];

        $userType = array_search($loginUrl, $loginTypes);
        abort_if(!$userType, 404);

        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        Session::put(SessionKey::ADMIN_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());

        $recaptcha = getWebConfig(name: 'recaptcha');

        return view(Auth::ADMIN_LOGIN, compact('recaptchaBuilder', 'recaptcha'))->with(['role' => $userType]);
    }

    // Define getIpAddress method inside the controller
    private function getIpAddress() {
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        if ($ipAddress == '::1') {
            $ipAddress = '127.0.0.1';
        } else {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipAddress = trim(end($ipList));
            } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
            }
        }

        return $ipAddress;
    }

    // public function login(LoginRequest $request): RedirectResponse
    // {
    //     // Check access by host address
    //     $ipAddress = $this->getIpAddress(); // Use $this->getIpAddress() to call the method
    //     //dd($ipAddress);
    //     $allowedIP = RemoteAccess::where('host_address', $ipAddress)->exists();

    //     if (!$allowedIP) {
    //         return redirect()->back()->withErrors([translate('You do not have access to this portal')]);
    //     }

    //     $recaptcha = getWebConfig(name: 'recaptcha');
    //     if (isset($recaptcha) && $recaptcha['status'] == 1) {
    //         $request->validate([
    //             'g-recaptcha-response' => [
    //                 function ($attribute, $value, $fail) {
    //                     $secretKey = getWebConfig(name: 'recaptcha')['secret_key'];
    //                     $response = $value;
    //                     $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $response;
    //                     $response = Http::get($url);
    //                     $response = $response->json();
    //                     if (!isset($response['success']) || !$response['success']) {
    //                         $fail(translate('ReCAPTCHA_Failed'));
    //                     }
    //                 },
    //             ],
    //         ]);
    //     } else if (strtolower(session(SessionKey::ADMIN_RECAPTCHA_KEY)) != strtolower($request['default_captcha_value'])) {
    //         Toastr::error(translate('ReCAPTCHA_Failed'));
    //         return back();
    //     }

    //     $admin = $this->admin->where('email', $request['email'])->first();

    //     if (isset($admin) && in_array($request['role'], [UserRole::ADMIN, UserRole::EMPLOYEE]) && $admin->status) {
    //         if ($this->adminService->isLoginSuccessful($request['email'], $request['password'], $request['remember'])) {

    //             // Get location by IP
    //             function getLatLong($ip) {
    //                 $url = "https://ipinfo.io/{$ip}/json";
    //                 $response = file_get_contents($url);
    //                 $data = json_decode($response, true);

    //                 if (isset($data['loc'])) {
    //                     list($latitude, $longitude) = explode(',', $data['loc']);
    //                     return [
    //                         'latitude' => $latitude,
    //                         'longitude' => $longitude,
    //                     ];
    //                 } else {
    //                     return [
    //                         'latitude' => 0,
    //                         'longitude' => 0,
    //                     ];
    //                 }
    //             }

    //             if ($ipAddress != "::1") {
    //                 $location = getLatLong($ipAddress);

    //                 // Save login logs
    //                 $logs = new LoginLogs;
    //                 $logs->role = $request['role'];
    //                 $logs->email = $request['email'];
    //                 $logs->ip_address = $ipAddress;
    //                 $logs->latitude = $location['latitude'] ?? 0;
    //                 $logs->longitude = $location['longitude'] ?? 0;
    //                 $logs->login = now();
    //                 $logs->save();
    //             }

    //             return redirect()->route('admin.dashboard.index');
    //         }
    //     }

    //     return redirect()->back()->withInput($request->only('email', 'remember'))
    //         ->withErrors([translate('credentials does not match or your account has been suspended')]);
    // }
    public function getPublicIp() {
        $clientIp = null;

        // Check for the X-Forwarded-For header (set by proxies)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get the first IP in the list (comma-separated)
            $clientIp = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        // Check for the X-Real-IP header (set by some proxies)
        elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $clientIp = $_SERVER['HTTP_X_REAL_IP'];
        }

        // Fallback to REMOTE_ADDR if no proxy headers exist
        else {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        // Ensure we only return an IPv4 address
        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $clientIp; // Valid IPv4 address
        }

        // If it's not an IPv4, return null or handle as needed
        return null;
    }


    public function login(LoginRequest $request): RedirectResponse
    {
        // Function to get the public IP using cURL
       
        // check access by host address
        // $ipAddress = ""; 
        // $ipAddress = $this->getPublicIp(); 
        //  // Use getPublicIp instead of $_SERVER['REMOTE_ADDR']
        // //dd($ipAddress);
        // if ($ipAddress == null) {
        //     return redirect()->back()->withErrors([translate('Failed to retrieve your public IP')]);
        // }

        // $allowedIP = RemoteAccess::where('host_address', $ipAddress)->exists();
        // if(!$allowedIP){
        //     function getLatLong($ip) {
        //         $url = "https://ipinfo.io/{$ip}/json";
        //         $response = file_get_contents($url);
        //         $data = json_decode($response, true);

        //         if (isset($data['loc'])) {
        //             list($latitude, $longitude) = explode(',', $data['loc']);
        //             return [
        //                 'latitude' => $latitude,
        //                 'longitude' => $longitude,
        //             ];
        //         } else {
        //             return [
        //                 'latitude' => 0,
        //                 'longitude' => 0,
        //             ];
        //         }
        //     }

        //     if($ipAddress != "::1"){
        //         $location = getLatLong($ipAddress);

        //         // logs
        //         $logs = new LoginLogs;
        //         $logs->role = $request['role'];
        //         $logs->email = $request['email'];
        //         $logs->ip_address = $ipAddress;
        //         $logs->latitude = $location['latitude'] ? $location['latitude'] : 0;
        //         $logs->longitude = $location['longitude'] ? $location['longitude'] : 0;
        //         $logs->login = now();
        //         if($request['role']=='admin' && $ipAddress!='110.227.221.102'){
        //             $logs->notification_status = 1;
        //         }
        //         $logs->save();
        //     }
        //     return redirect()->back()->withErrors([translate('You do not have access to this portal')]); 
        // }

        $recaptcha = getWebConfig(name: 'recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secretKey = getWebConfig(name: 'recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $response;
                        $response = Http::get($url);
                        $response = $response->json();
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('ReCAPTCHA_Failed'));
                        }
                    },
                ],
            ]);
        } else if(strtolower(session(SessionKey::ADMIN_RECAPTCHA_KEY)) != strtolower($request['default_captcha_value'])) {
            Toastr::error(translate('ReCAPTCHA_Failed'));
            return back();
        }

        $admin = $this->admin->where('email', $request['email'])->first();

        if (isset($admin) && in_array($request['role'], [UserRole::ADMIN, UserRole::EMPLOYEE]) && $admin->status) {
            if ($this->adminService->isLoginSuccessful($request['email'], $request['password'], $request['remember'])) {

                // start location retrieval using public IP
                function getLatLong($ip) {
                    $url = "https://ipinfo.io/{$ip}/json";
                    $response = file_get_contents($url);
                    $data = json_decode($response, true);

                    if (isset($data['loc'])) {
                        list($latitude, $longitude) = explode(',', $data['loc']);
                        return [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                        ];
                    } else {
                        return [
                            'latitude' => 0,
                            'longitude' => 0,
                        ];
                    }
                }

                // if($ipAddress != "::1"){
                //     $location = getLatLong($ipAddress);

                //     // logs
                //     $logs = new LoginLogs;
                //     $logs->role = $request['role'];
                //     $logs->email = $request['email'];
                //     $logs->ip_address = $ipAddress;
                //     $logs->latitude = $location['latitude'] ? $location['latitude'] : 0;
                //     $logs->longitude = $location['longitude'] ? $location['longitude'] : 0;
                //     $logs->login = now();
                //     if($request['role']=='admin' && $ipAddress!='110.227.221.102'){
                //         $logs->notification_status = 1;
                //     }
                //     $logs->save();
                // }

                return redirect()->route('admin.dashboard.index');
            }
        }

        return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors([translate('Credentials do not match or your account has been suspended')]);
    }


    public function logout(): RedirectResponse
    {
        // logs
        if(isset(auth('admin')->user()['email']) && !empty(auth('admin')->user()['email'])){
            $ip = $_SERVER['REMOTE_ADDR'];
            // $ip = '49.43.3.213';
            if($ip != "::1"){
                $logs = LoginLogs::where('email',auth('admin')->user()['email'])->where('ip_address',$ip)->latest()->first();
                if($logs){
                    $logs->logout = now();
                    $logs->save();
                }
            }
        }
        
        $this->adminService->logout();
        session()->flash('success', translate('logged out successfully'));
        return redirect('login/' . getWebConfig(name: 'admin_login_url'));
    }

}   