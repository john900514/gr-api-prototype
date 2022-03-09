<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum', 'can-access'])->get('/user', function (Request $request) {
    return $request->user()->toArray();
});

Route::middleware(['auth:sanctum', 'can-access'])->group(function() {
    Route::prefix('customers')->group(function() {
        Route::prefix('leads')->group(function() {
            Route::post('/', \App\Http\Controllers\Customers\Leads\LeadIntakeController::class."@create");
        });
    });
});
