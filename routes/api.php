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

// @todo - Basic Auth, which uses a personal access token after logging in the first time along with being allowed access.

Route::middleware(['auth:sanctum', 'can-access'])->group(function() {
    Route::prefix('customers')->group(function() {
        Route::prefix('leads')->group(function() {
            Route::post('/', \App\Http\Controllers\Customers\Leads\LeadIntakeController::class."@create");
        });
    });

    Route::prefix('reporting')->group(function() {
        Route::prefix('leads')->group(function() {
            Route::get('/', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_unique_leads");
            Route::get('/locations', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_unique_leads_by_location");

            Route::prefix('daily')->group(function() {
                Route::get('/', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_daily_leads");
                Route::get('/locations', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_daily_leads_by_location");
            });

            Route::prefix('organic')->group(function() {
                Route::get('/', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_organic_leads");
            });

            Route::prefix('utm')->group(function() {
                Route::get('/', \App\Http\Controllers\Clients\Reporting\LeadReportsController::class."@total_utm_leads");
            });
        });
    });
});
