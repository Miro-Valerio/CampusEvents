<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\OrganizerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);
Route::get('/organizers/{organizer}', [OrganizerController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-rsvps', [EventController::class, 'myRsvps']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/events/{event}/rsvp', [EventController::class, 'storeRsvp']);
    Route::delete('/events/{event}/rsvp', [EventController::class, 'cancelRsvp']);
    Route::post('/events/{event}/save', [EventController::class, 'toggleSave']);
});
