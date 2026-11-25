<?php

use App\Actions\Apartments\ActivateApartment;
use App\Actions\Apartments\CreateApartment;
use App\Actions\Apartments\DeactivateApartment;
use App\Actions\Apartments\UpdateApartment;
use App\Models\Apartment;
use Database\Seeders\ApartmentSeeder;
use Illuminate\Validation\ValidationException;

test('the seeder loads the 18 apartments with their capacities, once', function () {
    $this->seed(ApartmentSeeder::class);
    $this->seed(ApartmentSeeder::class);

    expect(Apartment::count())->toBe(18)
        ->and(Apartment::active()->count())->toBe(18)
        ->and(Apartment::where('number', '303')->value('capacity'))->toBe(5)
        ->and(Apartment::ordered()->pluck('number')->first())->toBe('102')
        ->and(Apartment::ordered()->pluck('number')->last())->toBe('307');
});

test('the seeder does not overwrite apartments edited from the app', function () {
    $this->seed(ApartmentSeeder::class);
    Apartment::where('number', '102')->update(['capacity' => 4, 'is_active' => false]);

    $this->seed(ApartmentSeeder::class);

    $apartment = Apartment::where('number', '102')->sole();
    expect($apartment->capacity)->toBe(4)->and($apartment->is_active)->toBeFalse();
});

test('an apartment is created active', function () {
    $apartment = app(CreateApartment::class)([
        'number' => ' 401 ',
        'capacity' => 3,
        'description' => 'Con balcón',
        'sort_order' => 190,
    ]);

    expect($apartment->number)->toBe('401')
        ->and($apartment->is_active)->toBeTrue()
        ->and($apartment->description)->toBe('Con balcón');
});

test('apartment numbers are unique', function () {
    Apartment::factory()->create(['number' => '401']);

    app(CreateApartment::class)(['number' => '401', 'capacity' => 3, 'sort_order' => 1]);
})->throws(ValidationException::class);

test('capacity must be at least one person', function () {
    app(CreateApartment::class)(['number' => '401', 'capacity' => 0, 'sort_order' => 1]);
})->throws(ValidationException::class);

test('an apartment can be updated keeping its own number', function () {
    $apartment = Apartment::factory()->create(['number' => '401', 'capacity' => 2]);

    app(UpdateApartment::class)($apartment, [
        'number' => '401',
        'capacity' => 4,
        'description' => '',
        'sort_order' => 5,
    ]);

    expect($apartment->fresh())
        ->capacity->toBe(4)
        ->description->toBeNull()
        ->sort_order->toBe(5);
});

test('an apartment can be deactivated and activated again', function () {
    $apartment = Apartment::factory()->create();

    app(DeactivateApartment::class)($apartment);
    expect($apartment->fresh()->is_active)->toBeFalse();

    app(ActivateApartment::class)($apartment);
    expect($apartment->fresh()->is_active)->toBeTrue();
});

test('an apartment with future reservations can not be deactivated')
    ->todo('Needs the reservations table (construction step 5).');
