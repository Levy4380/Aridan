<?php

use App\Livewire\Apartments\Index;
use App\Models\Apartment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the apartments screen lists apartments and counts the active ones', function () {
    Apartment::factory()->create(['number' => '102', 'capacity' => 3, 'sort_order' => 1]);
    Apartment::factory()->inactive()->create(['number' => '306', 'sort_order' => 2]);

    $this->get(route('apartments.index'))
        ->assertOk()
        ->assertSeeInOrder(['102', '3 personas', '306'])
        ->assertSee(__('apartments.description', ['active' => 1, 'total' => 2]))
        ->assertSee(__('nav.apartments'));
});

test('an apartment can be created from the modal', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->set('form.number', '401')
        ->set('form.capacity', 3)
        ->set('form.description', 'Con balcón')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(Apartment::where('number', '401')->sole())
        ->capacity->toBe(3)
        ->is_active->toBeTrue();
});

test('a repeated number shows an error on the field', function () {
    Apartment::factory()->create(['number' => '401']);

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.number', '401')
        ->set('form.capacity', 3)
        ->call('save')
        ->assertHasErrors(['form.number' => 'unique']);

    expect(Apartment::count())->toBe(1);
});

test('an apartment can be edited and deactivated from the modal', function () {
    $apartment = Apartment::factory()->create(['number' => '105', 'capacity' => 4]);

    Livewire::test(Index::class)
        ->call('edit', $apartment->id)
        ->assertSet('form.number', '105')
        ->set('form.capacity', 5)
        ->set('form.is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($apartment->fresh())
        ->capacity->toBe(5)
        ->is_active->toBeFalse();
});
