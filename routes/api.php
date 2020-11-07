<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

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

Route::group([
  'prefix' => 'account'
], function () {
  Route::post('send-otp', [AuthController::class, 'sendOTP']);
  Route::post('login', [AuthController::class, 'login'])->name('login');

  Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::post('update-user', [AuthController::class, 'updateUser']);
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('logout', [AuthController::class, 'logout']);
    });
});