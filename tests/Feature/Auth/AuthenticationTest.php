<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('auth.login.title'));
});

test('employees can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('employees can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

test('inactive employees can not log in', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => __('auth.inactive')]);

    $this->assertGuest();
});

test('inactive employees with a wrong password get the generic error', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => __('auth.failed')]);
});

test('an employee deactivated mid-session is logged out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('calendar'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('calendar'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('auth.inactive')]);

    $this->assertGuest();
});

test('employees can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

test('there is no public registration nor password reset', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
});
