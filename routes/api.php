<?php

use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
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
        Route::post("/user/create_payment_method", "createPaymentMethod");
        Route::get("/user/read", "read");
        Route::get("/user/read_all", "readAll");
        Route::get("/user/read_earning", "readEarning");
        Route::get("/user/read_all_earning", "readAllEarning");
        Route::get("/user/read_payment_method", "readPaymentMethod");
        Route::get("/user/read_all_payment_method", "readAllPaymentMethod");
        Route::get("/user/read_dashboard_data", "readDashboardData");
        Route::get("/user/initiate_identity_verification", "initiateIdentityVerification");
        Route::put("/user/update", "update");
        Route::put("/user/update_default_payment_method", "updateDefaultPaymentMethod");
        Route::delete("/user/delete", "delete");
        Route::delete("/user/delete_payment_method", "deletePaymentMethod");
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
        Route::post("/property/pay_dividend", "payDividend");
        Route::post("/property/calculate_potential", "calculatePotential");
        Route::get("/property/read", "read");
        Route::get("/property/read_all", "readAll");
        Route::get("/property/read_paid_dividend", "readPaidDividend");
        Route::put("/property/update", "update");
        Route::delete("/property/delete", "delete");
    });

    //All investment endpoints.
    Route::controller(InvestmentController::class)->group(function () {
        Route::post("/investment/create", "create");
        Route::get("/investment/read_all", "readAll");
        Route::get("/investment/read_user_specific", "readUserSpecific");
        Route::get("/investment/read_user_and_property_specific", "readUserAndPropertySpecific");
        Route::get("/investment/read_property_specific", "readPropertySpecific");
        Route::put("/investment/liquidate", "liquidate");
    });

    //All payment endpoints.
    Route::controller(PaymentController::class)->group(function () {
        Route::post("/payment/create", "create");
        Route::get("/payment/read", "read");
        Route::get("/payment/read_all", "readAll");
        Route::get("/payment/read_user_specific", "readUserSpecific");
        Route::get("/payment/read_all_currency", "readAllCurrency");
        Route::get("/payment/convert_currency", "convertCurrency");
        Route::delete("/payment/delete", "delete");
    });

    //All notification endpoints.
    Route::controller(NotificationController::class)->group(function () {
        Route::post("/notification/create", "create");
        Route::post("/notification/create_all", "createAll");
        Route::get("/notification/read", "read");
        Route::get("/notification/read_all", "readAll");
        Route::get("/notification/read_user_specific", "readUserSpecific");
        Route::put("/notification/update", "update");
        Route::delete("/notification/delete", "delete");
    });
});
