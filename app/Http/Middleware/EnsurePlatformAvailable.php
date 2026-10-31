<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::current()->maintenance_mode
            || $request->routeIs('login', 'login.page', 'login.post', 'health')
            || $request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        return response()->view('errors.maintenance', status: 503);
    }
}
