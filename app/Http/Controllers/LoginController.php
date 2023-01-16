<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Login;
use App\Custom\Authentication;

class LoginController extends Controller
{
    public function create(Request $request)
    {
        if (User::where("user_id", $request->request->get("user_id"))->exists()) {
            Login::updateOrCreate(["user_id" => $request->request->get("user_id"), "access_type" => $request->request->get("access_type"), "device_os" => $request->request->get("device_os"), "device_token" => $request->request->get("device_token")], $request->all());
            $auth = new Authentication();
            $expirable = false;
            if ($request->header("access-type") != "mobile") {
                $expirable = true;
            }
            return response()->json([
                "status" => true,
                "message" => "User logged in successfully.",
                "data" => [
                    "token" => $auth->encode($request->request->get("user_id"), $expirable)
                ]
            ], 201);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User not found."
            ], 404);
        }
    }

    public function read(Request $request)
    {
        if (Login::where("user_id", $request->request->get("user_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Login data retrieved successfully.",
                "data" => Login::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Login data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        return response()->json([
            "status" => true,
            "message" => "All login data retrieved successfully.",
            "data" => Login::latest()->get()
        ], 200);
    }

    public function delete(Request $request)
    {
        if (Login::where("user_id", $request->request->get("user_id"))->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->exists()) {
            Login::where("user_id", $request->request->get("user_id"))->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->delete();
            return response()->json([
                "status" => true,
                "message" => "User logged out successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Login data not found."
            ], 404);
        }
    }
}
