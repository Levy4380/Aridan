<?php

use App\Livewire\Guests\Drawer;
use App\Models\Guest;
use App\Models\User;
use Livewire\Livewire;

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

test('every screen carries the drawers, so they open without leaving the page', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertSeeHtml('data-test="reservation-panel"')
        ->assertSeeHtml('data-test="guest-drawer"')
        ->assertSeeHtml('data-test="apartment-drawer"')
        ->assertSeeHtml('data-test="employee-drawer"');
})->with(['calendar', 'availability', 'guests.index', 'apartments.index', 'employees.index']);

test('a guest file opens over any page from the URL', function () {
    $guest = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero']);

    $this->actingAs(User::factory()->create())
        ->get(route('calendar', ['cliente' => $guest->id]))
        ->assertOk()
        ->assertSee(__('guests.history'));
});

test('saving in a drawer tells the pages to refresh', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Drawer::class)
        ->call('open')
        ->set('form.first_name', 'Lucía')
        ->set('form.last_name', 'López')
        ->set('form.phone', '11 4444-0000')
        ->call('save')
        ->assertDispatched('guest-changed');

    Livewire::test(App\Livewire\Apartments\Drawer::class)
        ->call('open')
        ->set('form.number', '401')
        ->set('form.capacity', 2)
        ->call('save')
        ->assertDispatched('apartment-changed');
});

test('the header links to the screens that already exist', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar'))
        ->assertSee(route('calendar'))
        ->assertSee(route('apartments.index'))
        ->assertSee(route('employees.index'))
        ->assertSee(route('guests.index'))
        ->assertSee(route('availability'));
});
