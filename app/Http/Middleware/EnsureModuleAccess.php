<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $store = $request->user()->currentStore();

        if (! $store->hasModuleAccess($module)) {
            return redirect()
                ->route('accueil')
                ->with('error', 'Ce module n’est pas inclus dans votre forfait ou a été désactivé dans vos paramètres.');
        }

        return $next($request);
    }
}
