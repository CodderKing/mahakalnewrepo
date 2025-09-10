<?php

namespace App\Http\Middleware;
use App\Services\AdminService;
use Closure;
use App\Utils\Helpers;
use App\Models\RemoteAccess;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function __construct(private readonly AdminService $adminService)
     {
        //  $this->middleware('guest:admin', ['except' => ['logout']]);
     }

     // getpublicIP function 
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
    public function handle($request, Closure $next)
    {
        if (Auth::guard('admin')->check()) {
            // $ipAddress = $this->getPublicIp();
            // //dd($ipAddress);  // Check if the IP is being retrieved correctly

            // if ($ipAddress == null) {
            //     $this->adminService->logout();
            //     session()->flash('success', translate('logged out successfully'));
            //     return redirect('login/' . getWebConfig(name: 'admin_login_url'))->with('error', 'IP not found');
            // }

            // $allowedIP = RemoteAccess::where('host_address', $ipAddress)->exists();
            // //dd($allowedIP);  // Check if the IP exists in the database

            // if(!$allowedIP){
            //     $this->adminService->logout();
            //     session()->flash('success', translate('logged out successfully'));
            //     return redirect('login/' . getWebConfig(name: 'admin_login_url'))->with('error', 'IP not allowed');
            // }

            return $next($request);
        } else {
            abort(404);
        }
    }
}

