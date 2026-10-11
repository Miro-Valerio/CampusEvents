<?php

use App\Http\Controllers\EventBrowseController;
use App\Http\Controllers\EventManagementController;
use App\Http\Controllers\SessionAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('events.index');
});

Route::get('/login', [SessionAuthController::class, 'create'])->name('login');
Route::post('/login', [SessionAuthController::class, 'store'])->name('login.store');
Route::post('/logout', [SessionAuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/events', [EventBrowseController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventBrowseController::class, 'show'])->name('events.show');

Route::middleware('auth')->prefix('officer')->name('officer.')->group(function (): void {
    Route::resource('events', EventManagementController::class)->except(['show']);
});
