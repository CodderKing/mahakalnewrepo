<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\SessionKey;
use App\Enums\ViewPaths\Vendor\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\LoginRequest;
use App\Models\LoginLogs;
use App\Models\RemoteAccess;
use App\Repositories\VendorWalletRepository;
use App\Services\VendorService;
use App\Traits\RecaptchaTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use RecaptchaTrait;

    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepo,
        private readonly VendorService             $vendorService,
        private readonly VendorWalletRepository    $vendorWalletRepo,

    ) {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    public function generateReCaptcha(): void
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (Session::has(SessionKey::VENDOR_RECAPTCHA_KEY)) {
            Session::forget(SessionKey::VENDOR_RECAPTCHA_KEY);
        }
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $recaptchaBuilder->output();
    }

    public function getLoginView(): View
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        $recaptcha = getWebConfig(name: 'recaptcha');
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        return view(Auth::VENDOR_LOGIN[VIEW], compact('recaptchaBuilder', 'recaptcha'));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // check access by host address
        // $response = Http::get('https://api.ipify.org');
        // $ipAddress = $response->body();
        // $ipAddress = $_SERVER['REMOTE_ADDR'];
        // $allowedIP = RemoteAccess::where('host_address',$ipAddress)->exists();
        // if(!$allowedIP){
        //     return redirect()->back()->withErrors([translate('You do not have access to this portal')]); 
        // }
        $recaptcha = getWebConfig(name: 'recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = getWebConfig(name: 'recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response;
                        $response = Http::get($url);
                        $response = $response->json();
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('recaptcha_failed'));
                        }
                    },
                ],
            ]);
        } else {
            if ($recaptcha['status'] != 1 && strtolower($request->vendorRecaptchaKey) != strtolower(Session(SessionKey::VENDOR_RECAPTCHA_KEY))) {
                return response()->json(['error' => translate('captcha_failed') . '!']);
            }
        }
        $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['email']]);
        $vendoremployee = \App\Models\VendorEmployees::where('email', $request['email'])->where('status', 1)->first();
        if ($vendoremployee) {
            $passwordCheck = Hash::check($request['password'], $vendoremployee['password']);
            if (!$passwordCheck) {
                return response()->json(['error' => translate('credentials_doesnt_match') . '!']);
            } else {
                $vendor['type'] = $vendoremployee['type'] . "_employee";
            }
        } else if ($vendor) {
            $passwordCheck = Hash::check($request['password'], $vendor['password']);
            // if ($passwordCheck && $vendor['status'] !== 'approved' && $vendor['status'] !== 'onlyapproved') {
            //     return response()->json(['status' => $vendor['status']]);
            // }
        } else {
            return response()->json(['error' => translate('credentials_doesnt_match') . '!']);
        }
        if ($this->vendorService->isLoginSuccessful($vendor['type'], $request->email, $request->password, $request->remember)) {
            if ($this->vendorWalletRepo->getFirstWhere(params: ['id' => auth('seller')->id()]) === false) {
                $this->vendorWalletRepo->add($this->vendorService->getInitialWalletData(vendorId: auth('seller')->id()));
            }

            //start location            
            function getLatLong($ip)
            {
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

            $ip = $_SERVER['REMOTE_ADDR'];
            // $ip = '49.43.3.213';
            if ($ip !== "::1") {
                $location = getLatLong($ip);
                //end loction

                // logs
                $logs = new LoginLogs;
                $logs->role = 'vendor';
                $logs->email = $request['email'];
                $logs->ip_address = $ip;
                $logs->latitude = $location['latitude'] ? $location['latitude'] : 0;
                $logs->longitude = $location['longitude'] ? $location['longitude'] : 0;
                $logs->login = now();
                $logs->save();
            }

            Toastr::info(translate('welcome_to_your_dashboard') . '.');
            if ($vendor['type'] == 'seller') {
                $routes = route('vendor.dashboard.index');
            } elseif ($vendor['type'] == 'tour' || $vendor['type'] == 'tour_employee') {
                $routes = route('tour-vendor.dashboard.index');
            } elseif ($vendor['type'] == 'event' || $vendor['type'] == 'event_employee') {
                $routes = route('event-vendor.dashboard.index');
            } elseif ($vendor['type'] == 'trust' || $vendor['type'] == 'trust_employee') {
                $routes = route('trustees-vendor.dashboard.index');
            }
            return response()->json([
                'success' => translate('login_successful') . '!',
                'redirectRoute' => $routes,
            ]);
        } else {
            return response()->json(['error' => translate('credentials_doesnt_match') . '!']);
        }
    }

    public function logout(): RedirectResponse
    {
        // logs
        if (isset(auth('seller')->user()['email']) && !empty(auth('seller')->user()['email'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            // $ip = '49.43.3.213';
            if ($ip != "::1") {
                $logs = LoginLogs::where('email', auth('seller')->user()['email'])->where('ip_address', $ip)->latest()->first();
                if ($logs) {
                    $logs->logout = now();
                    $logs->save();
                }
            }
        }

        $this->vendorService->logout();
        Toastr::success(translate('logged_out_successfully') . '.');
        return redirect()->route('vendor.auth.login');
    }
}