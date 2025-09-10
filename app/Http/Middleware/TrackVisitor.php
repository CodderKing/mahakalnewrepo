<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Visitor;
use Illuminate\Support\Facades\Http;

class TrackVisitor
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        if (!Visitor::where('ip_address', $ip)->exists()) {
            $response = Http::get("http://ip-api.com/json/{$ip}");
            $city = null;
            $country = null;

            if ($response->ok()) {
                $data = $response->json();
                $city = $data['city'] ?? null;
                $country = $data['country'] ?? null;
            }

            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

            Visitor::create([
                'ip_address' => $ip,
                'url'        => $request->fullUrl(),
                'referer'    => $referer,
                'city'       => $city,
                'country'    => $country,
            ]);
        }

        return $next($request);
    }
}