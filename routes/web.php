<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;

Route::get('/', function () {
    return view('welcome');
});

// Route to show the registration form
Route::get('/people/create', [PersonController::class, 'create'])->name('people.create');

// Route to handle form submission
Route::post('/people', [PersonController::class, 'store'])->name('people.store');
