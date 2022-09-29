<?php

use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
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
        Route::get("/investment/read_all", "readAll");
        Route::get("/investment/read_user_specific", "readUserSpecific");
        Route::get("/investment/read_user_and_property_specific", "readUserAndPropertySpecific");
        Route::get("/investment/read_payment_specific", "readPaymentSpecific");
        Route::get("/investment/read_property_specific", "readPropertySpecific");
        Route::delete("/investment/delete", "delete");
    });

    //All payment endpoints.
    Route::controller(PaymentController::class)->group(function () {
        Route::post("/payment/create", "create");
        Route::get("/payment/read", "read");
        Route::get("/payment/read_all", "readAll");
        Route::get("/payment/read_user_specific", "readUserSpecific");
        Route::delete("/payment/delete", "delete");
    });

    //All notification endpoints.
    Route::controller(NotificationController::class)->group(function () {
        Route::post("/notification/create", "create");
        Route::post("/notification/create_all", "createAll");
        Route::get("/notification/read", "read");
        Route::get("/notification/read_all", "readAll");
        Route::get("/notification/read_user_specific", "readUserSpecific");
        Route::delete("/notification/delete", "delete");
    });

    //All payment method endpoints.
    Route::controller(PaymentMethodController::class)->group(function () {
        Route::post("/payment_method/create", "create");
        Route::get("/payment_method/read", "read");
        Route::get("/payment_method/read_all", "readAll");
        Route::get("/payment_method/read_user_specific", "readUserSpecific");
        Route::delete("/payment_method/delete", "delete");
    });
});
