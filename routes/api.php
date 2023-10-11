<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(static function () {

    Route::post('/token', [AuthController::class, 'create']);

    Route::middleware('auth:sanctum')->group(callback: static function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
