<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\SendSms;

class UserController extends Controller
{
    public function sendOtp(Request $request)
    {
        $send = new SendSms();
        $send->sendOtp($request->request->get("phone_number"));
        if ($send != false && isset($send)) {
            return response()->json([
                "status" => true,
                "message" => "Otp was successfully sent.",
                "data" => [
                    "otp" => $send
                ]
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to send otp."
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
