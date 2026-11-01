<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $store = request()->user()->currentStore();

        $members = $store->members()->whereKey('!=', request()->user()->id)->latest()->get();
        $modules = $this->modules();

        return view('Settings.config', compact('store', 'members', 'modules'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'payment_methods' => $request->input('payment_methods', ['mobile_money', 'card', 'cash']),
            'enabled_modules' => $request->has('enabled_modules')
                ? $request->input('enabled_modules')
                : [],
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email' => ['required', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'in:BJ,BF,CI,GW,ML,NE,SN,TG'],
            'currency' => ['required', 'in:XOF'],
            'timezone' => ['required', 'in:Africa/Abidjan,Africa/Bamako,Africa/Cotonou,Africa/Dakar,Africa/Lome,Africa/Niamey,Africa/Ouagadougou'],
            'date_format' => ['required', 'in:d/m/Y,m/d/Y,Y-m-d'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['required', 'in:mobile_money,card,cash'],
            'enabled_modules' => ['nullable', 'array'],
            'enabled_modules.*' => ['string', Rule::in(array_keys($this->modules()))],
        ]);

        $store = $request->user()->currentStore();

        if ($request->hasFile('logo')) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('stores/logos', 'public');
        }

        unset($validated['logo']);
        $validated['payment_methods'] = array_values($validated['payment_methods']);
        $validated['enabled_modules'] = array_values($validated['enabled_modules'] ?? []);
        $store->update($validated);

        return redirect()
            ->route('settings')
            ->with('status', 'Les paramètres de la boutique ont été enregistrés.');
    }

    public function addMember(Request $request): RedirectResponse
    {
        $store = $this->boutique($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'manager', 'seller'])],
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $validated['store_id'] = $store->id;
        $member = User::create($validated);

        return back()->with('status', 'Membre ajouté à l’équipe.');
    }

    public function removeMember(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->store_id === $this->boutique($request)->id, 404);
        $member->delete();

        return back()->with('status', 'Membre retiré de l’équipe.');
    }

    private function boutique(Request $request)
    {
        return $request->user()->currentStore();
    }

    private function modules(): array
    {
        return [
            'stock' => ['name' => 'Gestion des Stocks', 'description' => 'Produits, inventaire et fournisseurs.'],
            'pos' => ['name' => 'Interface de Caisse POS', 'description' => 'Ventes en magasin et commandes.'],
            'analytics' => ['name' => 'Analytique & Rapports', 'description' => 'Chiffre d’affaires, marges et dépenses.'],
            'shop' => ['name' => 'E-commerce', 'description' => 'Boutique en ligne et commandes web.'],
            'crm' => ['name' => 'Clientèle & B2B', 'description' => 'Clients, crédits et relances.'],
            'delivery' => ['name' => 'Gestion des Livraisons', 'description' => 'Zones, livreurs et bordereaux.'],
            'invoice' => ['name' => 'Facturation B2B', 'description' => 'Devis, factures et remises professionnelles.'],
            'copilot' => ['name' => 'Assistant Marchand', 'description' => 'Aide à l’analyse de votre activité.'],
        ];
    }
}
