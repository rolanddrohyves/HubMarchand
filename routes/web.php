<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthPasswordController;
use App\Http\Controllers\B2bBillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EcommerceController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'index'])->name('login');
    Route::get('/login', [AuthController::class, 'index'])->name('login.page');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
    Route::get('/mot-de-passe-oublie', [AuthPasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [AuthPasswordController::class, 'sendResetLink'])->middleware('throttle:login')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [AuthPasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [AuthPasswordController::class, 'reset'])->name('password.update');
});

Route::get('/boutique/{store}', [EcommerceController::class, 'shop'])->name('ecommerce.shop');
Route::post('/boutique/{store}/commander', [EcommerceController::class, 'checkout'])->name('ecommerce.checkout');
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['status' => 'ok', 'database' => 'ok']);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json(['status' => 'degraded', 'database' => 'unavailable'], 503);
    }
})->name('health');

Route::middleware('auth')->group(function () {
    Route::get('/email/verifier', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verifier/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/renvoyer', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::middleware('super-admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::get('/marchands', [SuperAdminController::class, 'merchants'])->name('merchants');
        Route::post('/marchands', [SuperAdminController::class, 'storeMerchant'])->name('merchants.store');
        Route::get('/marchands/{store}', [SuperAdminController::class, 'showMerchant'])->name('merchants.show');
        Route::get('/marchands/{store}/modifier', [SuperAdminController::class, 'editMerchant'])->name('merchants.edit');
        Route::put('/marchands/{store}', [SuperAdminController::class, 'updateMerchant'])->name('merchants.update');
        Route::delete('/marchands/{store}', [SuperAdminController::class, 'destroyMerchant'])->name('merchants.destroy');
        Route::put('/marchands/{store}/statut', [SuperAdminController::class, 'toggleMerchant'])->name('merchants.toggle');
        Route::put('/marchands/{store}/forfait', [SuperAdminController::class, 'assignPlan'])->name('merchants.plan');
        Route::get('/forfaits', [SuperAdminController::class, 'plans'])->name('plans');
        Route::post('/forfaits', [SuperAdminController::class, 'storePlan'])->name('plans.store');
        Route::put('/forfaits/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/forfaits/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');
        Route::get('/journal', [SuperAdminController::class, 'logs'])->name('logs');
        Route::get('/parametres', [SuperAdminController::class, 'systemSettings'])->name('settings');
        Route::put('/parametres', [SuperAdminController::class, 'updateSystemSettings'])->name('settings.update');
        Route::get('/administrateurs', [SuperAdminController::class, 'platformAdmins'])->name('admins');
        Route::post('/administrateurs', [SuperAdminController::class, 'storePlatformAdmin'])->name('admins.store');
        Route::put('/administrateurs/{user}', [SuperAdminController::class, 'updatePlatformAdmin'])->name('admins.update');
        Route::delete('/administrateurs/{user}', [SuperAdminController::class, 'destroyPlatformAdmin'])->name('admins.destroy');
    });
    Route::get('/exports/{resource}/{format}', [ExportController::class, 'download'])
        ->whereIn('format', ['pdf', 'xlsx', 'docx'])
        ->name('exports.download');
    Route::middleware(['merchant-subscription', 'verified'])->group(function () {
        Route::get('/accueil', [AccueilController::class, 'index'])->name('accueil');
        Route::middleware('module:analytics')->group(function () {
            Route::get('/analytique', [AnalyticsController::class, 'index'])->name('analytics.index');
            Route::post('/analytique/depenses', [AnalyticsController::class, 'storeExpense'])->name('analytics.expenses.store');
            Route::delete('/analytique/depenses/{expense}', [AnalyticsController::class, 'destroyExpense'])->name('analytics.expenses.destroy');
        });
        Route::middleware(['module:copilot', 'ai-access'])->group(function () {
            Route::get('/copilot', [AiCopilotController::class, 'index'])->name('copilot.index');
            Route::post('/copilot/contenu', [AiCopilotController::class, 'generateContent'])->name('copilot.content');
            Route::post('/copilot/post-social', [AiCopilotController::class, 'generateSocialPost'])->name('copilot.social');
            Route::post('/copilot/faq', [AiCopilotController::class, 'generateFaqResponse'])->name('copilot.faq');
            Route::post('/copilot/paniers/{salesOrder}/relance', [AiCopilotController::class, 'abandonedMessage'])->name('copilot.abandoned-message');
        });
        Route::middleware('module:shop')->group(function () {
            Route::get('/ecommerce', [EcommerceController::class, 'index'])->name('ecommerce.index');
            Route::put('/ecommerce', [EcommerceController::class, 'update'])->name('ecommerce.update');
            Route::put('/ecommerce/produits/{product}/publication', [EcommerceController::class, 'publish'])->name('ecommerce.products.publish');
        });
        Route::middleware('module:invoice')->group(function () {
            Route::get('/facturation-b2b', [B2bBillingController::class, 'index'])->name('billing.index');
            Route::post('/facturation-b2b', [B2bBillingController::class, 'store'])->name('billing.store');
            Route::get('/facturation-b2b/{document}', [B2bBillingController::class, 'show'])->name('billing.show');
            Route::post('/facturation-b2b/{document}/convertir', [B2bBillingController::class, 'convert'])->name('billing.convert');
            Route::put('/facturation-b2b/{document}/statut', [B2bBillingController::class, 'updateStatus'])->name('billing.status');
        });
        Route::middleware('module:delivery')->group(function () {
            Route::get('/livraisons', [DeliveryController::class, 'index'])->name('delivery.index');
            Route::put('/livraisons/commandes/{salesOrder}', [DeliveryController::class, 'updateOrder'])->name('delivery.orders.update');
            Route::post('/livraisons/livreurs', [DeliveryController::class, 'courier'])->name('delivery.couriers.store');
            Route::post('/livraisons/zones', [DeliveryController::class, 'zone'])->name('delivery.zones.store');
            Route::post('/livraisons/rapprochements', [DeliveryController::class, 'settle'])->name('delivery.settlements.store');
        });
        Route::get('/parametres', [SettingsController::class, 'index'])->name('settings');
        Route::put('/parametres', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/parametres/equipe', [SettingsController::class, 'addMember'])->name('settings.team.store');
        Route::delete('/parametres/equipe/{member}', [SettingsController::class, 'removeMember'])->name('settings.team.destroy');
        Route::middleware('module:stock')->group(function () {
            Route::get('/stocks', [InventoryController::class, 'index'])->name('inventory.index');
            Route::post('/stocks/produits', [InventoryController::class, 'store'])->name('inventory.products.store');
            Route::get('/stocks/produits/{product}', [InventoryController::class, 'show'])->name('inventory.products.show');
            Route::put('/stocks/produits/{product}', [InventoryController::class, 'update'])->name('inventory.products.update');
            Route::delete('/stocks/produits/{product}', [InventoryController::class, 'destroy'])->name('inventory.products.destroy');
            Route::post('/stocks/produits/{product}/mouvements', [InventoryController::class, 'movement'])->name('inventory.movements.store');
            Route::post('/stocks/fournisseurs', [InventoryController::class, 'supplier'])->name('inventory.suppliers.store');
            Route::post('/stocks/bons-de-commande', [InventoryController::class, 'purchaseOrder'])->name('inventory.purchase-orders.store');
            Route::post('/stocks/bons-de-commande/{purchaseOrder}/receptionner', [InventoryController::class, 'receivePurchaseOrder'])->name('inventory.purchase-orders.receive');
        });
        Route::middleware('module:pos')->group(function () {
            Route::get('/caisse', [PosController::class, 'index'])->name('pos.index');
            Route::post('/caisse/commandes', [PosController::class, 'store'])->name('pos.orders.store');
            Route::get('/caisse/commandes/{salesOrder}', [PosController::class, 'show'])->name('pos.orders.show');
            Route::put('/caisse/commandes/{salesOrder}/statut', [PosController::class, 'updateStatus'])->name('pos.orders.status');
        });
        Route::middleware('module:crm')->group(function () {
            Route::get('/clients', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('/clients', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('/clients/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::post('/clients/{customer}/paiements', [CustomerController::class, 'payment'])->name('customers.payments.store');
            Route::post('/clients/{customer}/relancer', [CustomerController::class, 'remind'])->name('customers.reminders.store');
        });
    });
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.post');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});
