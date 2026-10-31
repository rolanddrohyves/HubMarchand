<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->user()->currentStore();

        if (! $store->subscriptionPlan?->ai_included) {
            return redirect()
                ->route('accueil')
                ->with('error', 'Le module IA est disponible uniquement avec un forfait qui inclut Copilot IA.');
        }

        return $next($request);
    }
}
