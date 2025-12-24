<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\People\IndexPeople;
use App\Livewire\SocialWorkers\CreateSocialWorker;

Route::get('/', function () {
    return view('welcome');
});

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/create', CreatePerson::class)->name('people.create');


// Route to handle form submission
Route::post('/people', [PersonController::class, 'store'])->name('people.store');
Route::get('/social-workers/create', CreateSocialWorker::class)->name('social-workers.create');

