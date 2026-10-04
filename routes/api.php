<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\AttendanceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

ApiRoute::group(['namespace' => 'App\Http\Controllers'], function () {
    ApiRoute::get('purchased-module', ['as' => 'api.purchasedModule', 'uses' => 'HomeController@installedModule']);
});

Route::post('auth/token', [AttendanceApiController::class, 'issueToken'])->name('api.auth.token');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('attendance/check-in', [AttendanceApiController::class, 'checkIn'])
        ->name('api.attendance.check-in');
    Route::delete('auth/token', function (Request $request) {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token revoked successfully.']);
    })->name('api.auth.token.revoke');
});
