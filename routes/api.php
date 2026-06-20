<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\FlightController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('flights')->group(function () {
    Route::get('search', [FlightController::class, 'search']);
});
 
Route::prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'store']);
    Route::get('{reference}', [BookingController::class, 'show']);
});