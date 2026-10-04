<?php

use App\Http\Controllers\Api\AttendanceCheckInController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/attendance/check-in', AttendanceCheckInController::class)
    ->middleware('auth:sanctum');

Route::post('/auth/token', TokenController::class)
    ->middleware('throttle:6,1');
