<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect('/calendario');
    $this->get(route('calendar'))->assertRedirect(route('login'));
});

test('the calendar is the home page for authenticated employees', function () {
    $user = User::factory()->create(['name' => 'Ana Gómez', 'color' => '#81c784']);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertOk()
        ->assertSee(__('nav.calendar'))
        ->assertSee('Ana Gómez')
        ->assertSee('background-color: #81c784', escape: false);
});

test('the header links to the screens that already exist', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar'))
        ->assertSee(route('calendar'))
        ->assertSee(route('apartments.index'))
        ->assertSee(route('employees.index'))
        ->assertDontSee(__('nav.guests'));
});
