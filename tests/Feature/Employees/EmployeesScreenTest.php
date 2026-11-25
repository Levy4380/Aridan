<?php

use App\Actions\Employees\DeactivateEmployee;
use App\Livewire\Employees\Index;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->me = User::factory()->create(['name' => 'Tomás', 'color' => '#4fc3f7']);
    $this->actingAs($this->me);
});

test('the employees screen lists every employee, inactive ones included', function () {
    User::factory()->inactive()->create(['name' => 'Víctor']);

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Tomás')
        ->assertSee('Víctor')
        ->assertSee(__('common.inactive'));
});

test('a new employee can be created and log in', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->assertSet('form.color', '#81c784') // first palette color nobody uses
        ->set('form.name', 'Ana Gómez')
        ->set('form.email', 'Ana@Example.com')
        ->set('form.password', 'secreto123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $ana = User::where('email', 'ana@example.com')->sole();
    expect($ana->color)->toBe('#81c784')
        ->and($ana->is_active)->toBeTrue()
        ->and(Hash::check('secreto123', $ana->password))->toBeTrue();
});

test('a repeated color is allowed but warned about', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->call('pickColor', '#4FC3F7')
        ->assertSee(__('warnings.color_in_use', ['name' => 'Tomás']))
        ->set('form.name', 'Ana Gómez')
        ->set('form.email', 'ana@example.com')
        ->set('form.password', 'secreto123')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'ana@example.com')->value('color'))->toBe('#4fc3f7');
});

test('a custom color can be used', function () {
    $victor = User::factory()->create(['color' => '#81c784']);

    Livewire::test(Index::class)
        ->call('edit', $victor->id)
        ->set('form.color', '#1E3A8A')
        ->call('save')
        ->assertHasNoErrors();

    expect($victor->fresh()->color)->toBe('#1e3a8a');
});

test('another employee can be deactivated and reactivated', function () {
    $victor = User::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $victor->id)
        ->set('form.is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($victor->fresh()->is_active)->toBeFalse();

    Livewire::test(Index::class)
        ->call('edit', $victor->id)
        ->set('form.is_active', true)
        ->call('save');

    expect($victor->fresh()->is_active)->toBeTrue();
});

test('an employee can not deactivate themselves', function () {
    app(DeactivateEmployee::class)($this->me, $this->me);
})->throws(ValidationException::class);

test('editing an employee keeps their password', function () {
    $victor = User::factory()->create(['password' => Hash::make('original')]);

    Livewire::test(Index::class)
        ->call('edit', $victor->id)
        ->set('form.name', 'Víctor Pérez')
        ->call('save')
        ->assertHasNoErrors();

    expect($victor->fresh()->name)->toBe('Víctor Pérez')
        ->and(Hash::check('original', $victor->fresh()->password))->toBeTrue();
});
