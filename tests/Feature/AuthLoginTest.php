<?php

use App\Models\User;

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'marchand@hubmarchand.test',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertRedirect('/accueil');
    $this->assertAuthenticatedAs($user);
});

test('web responses include baseline security headers', function () {
    $this->get('/login')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'marchand@hubmarchand.test',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->from('/')->post('/login', [
        'email' => 'marchand@hubmarchand.test',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login is rate limited after repeated failed attempts', function () {
    $payload = [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ];

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', $payload)->assertSessionHasErrors('email');
    }

    $this->post('/login', $payload)->assertStatus(429);
});
