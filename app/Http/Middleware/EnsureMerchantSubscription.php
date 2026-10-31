<?php

namespace App\Http\Middleware;

use App\Notifications\SubscriptionExpiredNotification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return redirect()->route('super-admin.index');
        }

        $store = $user?->currentStore();

        if ($store?->suspendIfSubscriptionExpired()) {
            $store->user?->notify(new SubscriptionExpiredNotification($store));
        }

        abort_unless($store?->hasActiveSubscription(), 403, 'Votre boutique ne dispose pas d’un abonnement actif.');

        return $next($request);
    }
}
