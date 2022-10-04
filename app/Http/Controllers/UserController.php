<?php

namespace App\Http\Controllers;

use App\Custom\Authentication;
use App\Custom\NotificationManager;
use Illuminate\Http\Request;
use App\Custom\OtpManager;
use App\Models\Earning;
use App\Models\Referral;
use App\Models\User;

class UserController extends Controller
{
    public function sendOtp(Request $request)
    {
        /*$send = new OtpManager();
        $status = $send->sendOtp($request->request->get("phone_number"));
        if ($status != false && isset($status)) {
            return response()->json([
                "status" => true,
                "message" => "Otp was successfully sent."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to send otp."
            ], 500);
        }*/
        return response()->json([
            "status" => true,
            "message" => "The otp was not sent because our twilio credit is exhausted. But for testing purposes, this response is successful."
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        /*$send = new OtpManager();
        $auth = new Authentication();
        $status = $send->verifyOtp($request->request->get("phone_number"), $request->request->get("otp"));
        if ($status != false && isset($status)) {
            if ($status->status == "approved") {
                $user_exists = User::where("phone_number", $request->request->get("phone_number"))->exists();
                return response()->json([
                    "status" => true,
                    "message" => "Otp was successfully verified.",
                    "data" => [
                        "token" => $auth->encode($request->request->get("phone_number")),
                        "user_exists" => $user_exists
                    ]
                ], 200);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "The otp verification was unsuccessful. Code is incorrect."
                ], 400);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to verify otp."
            ], 500);
        }*/
        $auth = new Authentication();
        $user_exists = User::where("phone_number", $request->request->get("phone_number"))->exists();
        return response()->json([
            "status" => true,
            "message" => "The otp was not verified because our twilio credit is exhausted. But for testing purposes, this response is successful.",
            "data" => [
                "token" => $auth->encode($request->request->get("phone_number")),
                "user_exists" => $user_exists
            ]
        ], 200);
    }

    public function create(Request $request)
    {
        $has_referral = false;
        if ($request->request->has("referral_code") && $request->filled("referral_code")) {
            if (User::where("referral_code", $request->request->get("referral_code"))->exists()) {
                $referrer_phone_number = User::where("referral_code", $request->request->get("referral_code"))->value("phone_number");
                $referree_phone_number = $request->request->get("phone_number");
                if (!Referral::where("referrer_phone_number", $referrer_phone_number)->where("referree_phone_number", $referree_phone_number)->exists() && !Referral::where("referrer_phone_number", $referree_phone_number)->where("referree_phone_number", $referrer_phone_number)->exists()) {
                    $has_referral = true;
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Invalid referral code."
                ], 404);
            }
            $request->request->remove("referral_code");
        }
        do {
            $alphabets = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
            $referral_code = "";
            for ($i = 0; $i < 3; $i++) {
                $referral_code .= $alphabets[rand(0, 25)] . rand(0, 9);
            }
        } while (sizeof(User::where("referral_code", $referral_code)->get()) != 0);
        $request->request->add(["referral_code" => $referral_code]);
        User::firstOrCreate(["user_id" => $request->request->get("user_id")], $request->all());
        User::find($request->request->get("user_id"))->login()->updateOrCreate(["user_id" => $request->request->get("user_id"), "access_type" => $request->request->get("access_type"), "device_token" => $request->request->get("device_token")], $request->all());
        if ($has_referral) {
            $referral_payment_usd = 5;

            $referrer_balance = User::where("phone_number", $referrer_phone_number)->value("balance_usd");
            $new_referrer_balance = $referrer_balance + $referral_payment_usd;
            User::where("phone_number", $referrer_phone_number)->update(["balance_usd" => $new_referrer_balance]);
            if (User::where("phone_number", $referrer_phone_number)->exists()) {
                $referrer_user_id = User::where("phone_number", $referrer_phone_number)->value("user_id");
                $notification_manager = new NotificationManager();
                $notification_manager->sendNotification(array(
                    "receiver_user_id" => $referrer_user_id,
                    "title" => "Referral bonus received!!!",
                    "body" => "You have just received $" . $referral_payment_usd . " in your balance because someone joined Megalo with your referral code. Keep referring people to earn more!",
                    "tappable" => true,
                    "redirection_page" => "balance",
                    "redirection_page_id" => ""
                ), array(), "user_specific");
            }

            $referree_balance = User::where("phone_number", $referree_phone_number)->value("balance_usd");
            $new_referree_balance = $referree_balance + $referral_payment_usd;
            User::where("phone_number", $referree_phone_number)->update(["balance_usd" => $new_referree_balance]);
            if (User::where("phone_number", $referrer_phone_number)->exists()) {
                $referree_user_id = User::where("phone_number", $referree_phone_number)->value("user_id");
                $notification_manager = new NotificationManager();
                $notification_manager->sendNotification(array(
                    "receiver_user_id" => $referree_user_id,
                    "title" => "Referral bonus received!!!",
                    "body" => "You have just received $" . $referral_payment_usd . " in your balance because you joined Megalo with someone's referral code. You can earn more if you refer someone too.",
                    "tappable" => true,
                    "redirection_page" => "balance",
                    "redirection_page_id" => ""
                ), array(), "user_specific");
            }
        }
        $auth = new Authentication();
        return response()->json([
            "status" => true,
            "message" => "User registered successfully.",
            "data" => [
                "token" => $auth->encode($request->request->get("user_id"))
            ]
        ], 201);
    }

    public function read(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "User data retrieved successfully.",
            "data" => User::where("user_id", $request->request->get("user_id"))->get()
        ], 200);
    }

    public function readAll()
    {
        if (sizeof(User::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All user data retrieved successfully.",
                "data" => User::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No user data found."
            ], 404);
        }
    }

    public function readEarning(Request $request)
    {
        if (sizeof(Earning::where("user_id", $request->request->get("user_id"))->where("property_id", $request->get("property_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "User earning data retrieved successfully.",
                "data" => Earning::where("user_id", $request->request->get("user_id"))->where("property_id", $request->get("property_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User earning data not found."
            ], 404);
        }
    }

    public function readAllEarning(Request $request)
    {
        if (sizeof(Earning::where("user_id", $request->request->get("user_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All user earning data retrieved successfully.",
                "data" => Earning::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No user earning data found."
            ], 404);
        }
    }

    public function update(Request $request)
    {
        User::find($request->request->get("user_id"))->update($request->all());
        return response()->json([
            "status" => true,
            "message" => "User data updated successfully.",
        ], 200);
    }

    public function delete(Request $request)
    {
        User::find($request->request->get("user_id"))->login()->delete();
        User::find($request->request->get("user_id"))->investment()->delete();
        User::find($request->request->get("user_id"))->notificationSender()->delete();
        User::find($request->request->get("user_id"))->notificationReceiver()->delete();
        User::find($request->request->get("user_id"))->payment()->delete();
        User::find($request->request->get("user_id"))->paymentMethod()->delete();
        User::find($request->request->get("user_id"))->earning()->delete();
        User::destroy($request->request->get("user_id"));
        return response()->json([
            "status" => true,
            "message" => "User deleted successfully."
        ], 200);
    }
}
