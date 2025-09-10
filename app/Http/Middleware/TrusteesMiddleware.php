<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrusteesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('trust')->user();
        if (!$user) {
            return redirect()->route('vendor.auth.login');
        }
        $userStatus = $user->status ?? '';
        if ($userStatus === 'approved') {
            return $next($request);
        }
        if (in_array($userStatus, ['hold', 'suspended', 'pending', 'rejected'])) {
            if (!empty($user->pancard_image) && !empty($user->bank_name)) {
                return $next($request);
            }
            $allowedRoutes = [
                'trustees-vendor.profile.update',
                'trustees-vendor.profile.profile-edit',
                'trustees-vendor.dashboard.index',
                'trustees-vendor.message.*',
                'trustees-vendor.messages.*',
                'trustees-vendor.profile.update2',
                'trustees-vendor.profile.delete-image',
            ];
            $currentRouteName = $request->route()->getName();
            foreach ($allowedRoutes as $allowedRoute) {
                if (\Str::is($allowedRoute, $currentRouteName)) {
                    return $next($request);
                }
            }

            toastr()->error('Welcome to Mahakal.com! Please complete your profile to unlock full access to your dashboard features.');
            return redirect()->route('trustees-vendor.profile.update', [$user->relation_id]);
        }
        toastr()->error('Unauthorized access.');
        return redirect()->route('trustees-vendor.profile.update', [$user->relation_id]);
        // auth()->guard('trust')->logout();
        // return redirect()->route('vendor.auth.login');
    }
}
