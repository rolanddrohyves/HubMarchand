<?php

namespace App\Http\Controllers;

use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isSuperAdmin() ? 'super-admin.index' : 'accueil');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (! $request->user()->isSuperAdmin() && ! $request->user()->hasVerifiedEmail()) {
                $request->user()->sendEmailVerificationNotification();

                return redirect()->route('verification.notice');
            }

            if (! $request->user()->isSuperAdmin() && ($store = $request->user()->currentStore())->suspendIfSubscriptionExpired()) {
                $store->user?->notify(new SubscriptionExpiredNotification($store));

                return redirect()->route('notifications.index')->with('status', 'Votre abonnement a expiré. Votre compte est suspendu.');
            }

            if (! $request->user()->isSuperAdmin() && ! $request->user()->currentStore()->hasActiveSubscription()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => ['Votre boutique ne dispose pas d’un abonnement actif. Contactez HubMarchand.'],
                ]);
            }

            return redirect()->intended($request->user()->isSuperAdmin() ? route('super-admin.index') : route('accueil'));
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
