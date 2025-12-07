<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;
use App\Livewire\People\CreatePerson;

Route::get('/', function () {
    return view('welcome');
});

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/create', CreatePerson::class)->name('people.create');

// Route to show the registration form
//Route::get('/people/create', [PersonController::class, 'create'])->name('people.create');

// Route to handle form submission
Route::post('/people', [PersonController::class, 'store'])->name('people.store');

