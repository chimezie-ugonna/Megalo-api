<?php

namespace App\Http\Controllers;

use App\Custom\Authentication;
use Illuminate\Http\Request;
use App\Custom\SendSms;
use App\Models\User;

class UserController extends Controller
{
    public function sendOtp(Request $request)
    {
        $send = new SendSms();
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
        }
    }

    public function verifyOtp(Request $request)
    {
        $send = new SendSms();
        $auth = new Authentication();
        $status = $send->verifyOtp($request->request->get("phone_number"), $request->request->get("otp"));
        if ($status != false && isset($status)) {
            if ($status->status == "approved") {
                $new_user = false;
                if (!User::where("phone_number", $request->request->get("phone_number"))) {
                    $new_user = true;
                }
                return response()->json([
                    "status" => true,
                    "message" => "Otp was successfully verified.",
                    "data" => [
                        "token" => $auth->encode($request->request->get("phone_number")),
                        "new_user" => $new_user
                    ]
                ], 200);
            } else if ($status->status == "pending") {
                return response()->json([
                    "status" => false,
                    "message" => "The otp verification process is still pending."
                ], 500);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "The otp verification process was cancelled."
                ], 500);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to verify otp."
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $first_name = "";
        $last_name = "";
        if ($request->request->has("full_name")) {
            $full_name_split = explode(" ", $request->request->get("full_name"), 2);
            $first_name = $full_name_split[0];
            if (count($full_name_split) > 1) {
                $last_name = $full_name_split[1];
            }
        }

        $request->request->add([
            "first_name" => $first_name,
            "last_name" => $last_name,
            "device_token" => request()->header("device_token"),
            "device_brand" => request()->header("device_brand"),
            "device_model" => request()->header("device_model"),
            "app_version" => request()->header("app_version"),
            "os_version" => request()->header("os_version")
        ]);
        User::firstOrCreate(["user_id" => $request->request->get("user_id")], $request->all());
        User::find($request->request->get("user_id"))->logins()->updateOrCreate(["user_id" => $request->request->get("user_id"), "device_token" => $request->request->get("device_token")], $request->all());
        $auth = new Authentication();
        return response()->json([
            "status" => true,
            "message" => "User created successfully.",
            "data" => [
                "token" => $auth->encode($request->request->get("user_id"))
            ]
        ], 201);
    }

    public function read(Request $request)
    {
    }

    public function readAll()
    {
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
    }
}
