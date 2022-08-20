<?php

namespace App\Http\Controllers;

use App\Custom\Authentication;
use Illuminate\Http\Request;
use App\Custom\SendSms;

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
                return response()->json([
                    "status" => true,
                    "message" => "Otp was successfully verified.",
                    "data" => [
                        //"theme" => Users::find($request->request->get("user_id"))->value("theme"),
                        "token" => $auth->encode($request->request->get("phone_number"))
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
