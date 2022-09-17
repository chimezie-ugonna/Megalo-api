<?php

use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckHeader;
use App\Http\Middleware\IncomingDataValidation;
use App\Http\Middleware\TokenValidation;
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

Route::middleware([CheckHeader::class, IncomingDataValidation::class, TokenValidation::class])->prefix("v1")->group(function () {
    //All user endpoints.
    Route::controller(UserController::class)->group(function () {
        Route::post("/user/send_otp", "sendOtp");
        Route::post("/user/verify_otp", "verifyOtp");
        Route::post("/user/create", "create");
        Route::get("/user/read", "read");
        Route::get("/user/read_all", "readAll");
        Route::put("/user/update", "update");
        Route::delete("/user/delete", "delete");
    });

    //All login endpoints.
    Route::controller(LoginController::class)->group(function () {
        Route::post("/login/create", "create");
        Route::get("/login/read", "read");
        Route::get("/login/read_all", "readAll");
        Route::delete("/login/delete", "delete");
    });

    //All property endpoints.
    Route::controller(PropertyController::class)->group(function () {
        Route::post("/property/create", "create");
        Route::get("/property/read", "read");
        Route::get("/property/read_all", "readAll");
        Route::put("/property/update", "update");
        Route::delete("/property/delete", "delete");
    });

    //All investment endpoints.
    Route::controller(InvestmentController::class)->group(function () {
        Route::post("/investment/create", "create");
        Route::get("/investment/read", "read");
        Route::get("/investment/read_all", "readAll");
        Route::get("/investment/read_specific", "readSpecific");
        Route::put("/investment/update", "update");
        Route::delete("/investment/delete", "delete");
    });
});
