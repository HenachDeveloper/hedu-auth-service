<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JwksController;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => ['status' => 'ok', 'service' => 'auth-service']);
Route::get('jwks', JwksController::class);

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt');
});

Route::prefix('internal')->middleware(['service', 'throttle:internal'])->group(function () {
    Route::post('companies', [CompanyController::class, 'store']);
    Route::get('companies/{code}', [CompanyController::class, 'show']);
    Route::patch('companies/{code}/status', [CompanyController::class, 'status']);
    Route::post('companies/{code}/users', [CompanyController::class, 'addUser']);
});
