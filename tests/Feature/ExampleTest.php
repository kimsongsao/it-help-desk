<?php

use App\Models\User;

test('guests are redirected from the home page to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('every page credits the copyright holder', function () {
    $this->get(route('login'))
        ->assertSee('Kdigitized Solutions')
        ->assertSee('href="https://kdigitized.com"', false);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('&copy; '.now()->year, false)
        ->assertSee('Kdigitized Solutions');
});
