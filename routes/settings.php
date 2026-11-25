<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('perfil', 'perfil/datos');

    Route::livewire('perfil/datos', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('perfil/contrasena', 'pages::settings.security')->name('security.edit');
});
