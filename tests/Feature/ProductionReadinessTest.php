<?php

use App\Models\SystemSetting;

test('health endpoint reports application availability', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'database' => 'ok']);
});

test('payment providers are disabled by default', function () {
    expect(config('payments.enabled'))->toBeFalse()
        ->and(config('payments.default'))->toBe('manual');
});

test('configured brand colors are exposed as safe css variables', function () {
    SystemSetting::current()->update([
        'primary_color' => '#123456',
        'secondary_color' => '#654321',
        'accent_color' => '#abcdef',
    ]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('--brand-primary: #123456;', false)
        ->assertSee('--brand-secondary: #654321;', false)
        ->assertSee('--brand-accent: #abcdef;', false);
});
