<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('employee:create creates an active employee who can log in', function () {
    $this->artisan('employee:create', [
        '--name' => 'Ana Gómez',
        '--email' => 'Ana@Example.com',
        '--color' => '#F06292',
        '--password' => 'secreto123',
    ])->assertSuccessful();

    $employee = User::sole();

    expect($employee->name)->toBe('Ana Gómez')
        ->and($employee->email)->toBe('ana@example.com')
        ->and($employee->color)->toBe('#f06292')
        ->and($employee->is_active)->toBeTrue()
        ->and(Hash::check('secreto123', $employee->password))->toBeTrue();

    $this->post(route('login.store'), ['email' => 'ana@example.com', 'password' => 'secreto123']);

    $this->assertAuthenticatedAs($employee);
});

test('employee:create rejects a repeated email', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->artisan('employee:create', [
        '--name' => 'Ana Gómez',
        '--email' => 'ana@example.com',
        '--color' => '#f06292',
        '--password' => 'secreto123',
    ])->assertFailed();

    expect(User::count())->toBe(1);
});

test('employee:create rejects a color that is not a hex value', function () {
    $this->artisan('employee:create', [
        '--name' => 'Ana Gómez',
        '--email' => 'ana@example.com',
        '--color' => 'rosa',
        '--password' => 'secreto123',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});
