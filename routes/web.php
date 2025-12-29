<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\SocialWorkers\EditSocialWorker;
use App\Livewire\SocialWorkers\CreateSocialWorker;
use App\Livewire\SocialWorkers\IndexSocialWorkers;

Route::get('/', function () {
    return view('welcome');
});

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/create', CreatePerson::class)->name('people.create');
// Route to handle form submission
Route::get('/social-workers/create', CreateSocialWorker::class)->name('social-workers.create');

Route::get('/social-workers', IndexSocialWorkers::class)->name('social-workers.index');
Route::get('/social-workers/{socialWorker}/edit', EditSocialWorker::class)->name('social-workers.edit');

