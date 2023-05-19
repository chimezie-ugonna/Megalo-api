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
        Login::updateOrCreate(["user_id" => $request->request->get("user_id"), "access_type" => $request->request->get("access_type"), "device_os" => $request->request->get("device_os"), "device_token" => $request->request->get("device_token")], $request->all());
        $auth = new Authentication();
        return response()->json([
            "status" => true,
            "message" => "User logged in successfully.",
            "data" => ["token" => $auth->encode($request->request->get("user_id"))]
        ], 201);
    }

    public function read(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "Login data retrieved successfully.",
            "data" => Login::where("user_id", $request->request->get("user_id"))->latest("updated_at")->get()
        ], 200);
    }

    public function readAll(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All login data retrieved successfully.",
            "data" => Login::latest("updated_at")->simplePaginate($request->get("item_count_per_page"))
        ], 200);
    }

    public function updateDeviceToken(Request $request)
    {
        $login = Login::where("user_id", $request->request->get("user_id"))->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->first();
        $login->device_token = $request->request->get("device_token");
        $login->device_token_updated_at = now();
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
        if ($request->has("everywhere") && $request->filled("everywhere") && $request->get("everywhere")) {
            Login::where("user_id", $request->request->get("user_id"))->delete();
            return response()->json([
                "status" => true,
                "message" => "User logged out successfully."
            ], 200);
        } else {
            Login::where("user_id", $request->request->get("user_id"))->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->delete();
            return response()->json([
                "status" => true,
                "message" => "User logged out successfully."
            ], 200);
        }
    }
}
