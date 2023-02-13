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
            return response()->json([
                "status" => true,
                "message" => "User logged in successfully.",
                "data" => ["token" => $auth->encode($request->request->get("user_id"))]
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
        return response()->json([
            "status" => true,
            "message" => "Login data retrieved successfully.",
            "data" => Login::where("user_id", $request->request->get("user_id"))->latest("updated_at")->get()
        ], 200);
    }

    public function readAll()
    {
        return response()->json([
            "status" => true,
            "message" => "All login data retrieved successfully.",
            "data" => Login::latest("updated_at")->get()
        ], 200);
    }

    public function updateDeviceToken(Request $request)
    {
        $login = Login::where("device_token", $request->header("device-token", ""));
        $login->device_token = $request->request->get("device_token");
        $login->device_token_updated_at = now()->toDateTimeString();
        $login->timestamps = false;
        $login->save();
        return response()->json([
            "status" => true,
            "message" => "Device token updated successfully.",
            "data" => ["device_token" => $request->request->get("device_token")]
        ], 200);
    }

    public function delete(Request $request)
    {
        if ($request->request->has("everywhere") && $request->filled("everywhere") && $request->request->get("everywhere")) {
            if (Login::where("user_id", $request->request->get("user_id"))->exists()) {
                Login::where("user_id", $request->request->get("user_id"))->delete();
                return response()->json([
                    "status" => true,
                    "message" => "User logged out successfully."
                ], 200);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "No login data found."
                ], 404);
            }
        } else {
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
}
