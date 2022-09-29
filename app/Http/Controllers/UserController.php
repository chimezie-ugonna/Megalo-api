<?php

namespace App\Http\Controllers;

use App\Custom\Authentication;
use Illuminate\Http\Request;
use App\Custom\OtpManager;
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
        User::firstOrCreate(["user_id" => $request->request->get("user_id")], $request->all());
        User::find($request->request->get("user_id"))->login()->updateOrCreate(["user_id" => $request->request->get("user_id"), "access_type" => $request->request->get("access_type"), "device_token" => $request->request->get("device_token")], $request->all());
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
        if (User::find($request->request->get("user_id"))) {
            return response()->json([
                "status" => true,
                "message" => "User data retrieved successfully.",
                "data" => User::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User data not found."
            ], 404);
        }
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

    public function update(Request $request)
    {
        if (User::find($request->request->get("user_id"))) {
            User::find($request->request->get("user_id"))->update($request->all());
            return response()->json([
                "status" => true,
                "message" => "User data updated successfully.",
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        if (User::find($request->request->get("user_id"))) {
            User::find($request->request->get("user_id"))->login()->delete();
            User::find($request->request->get("user_id"))->investment()->delete();
            User::find($request->request->get("user_id"))->notificationSender()->delete();
            User::find($request->request->get("user_id"))->notificationReceiver()->delete();
            User::find($request->request->get("user_id"))->payment()->delete();
            User::find($request->request->get("user_id"))->paymentMethod()->delete();
            User::destroy($request->request->get("user_id"));
            return response()->json([
                "status" => true,
                "message" => "User deleted successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User data not found."
            ], 404);
        }
    }
}
