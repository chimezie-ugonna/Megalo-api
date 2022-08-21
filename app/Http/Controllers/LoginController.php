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
        $request->request->add([
            "device_token" => request()->header("device_token"),
            "device_brand" => request()->header("device_brand"),
            "device_model" => request()->header("device_model"),
            "app_version" => request()->header("app_version"),
            "os_version" => request()->header("os_version")
        ]);
        if (User::find($request->request->get("user_id"))) {
            Login::updateOrCreate(["user_id" => $request->request->get("user_id"), "device_token" => $request->request->get("device_token")], $request->all());
            $auth = new Authentication();
            return response()->json([
                "status" => true,
                "message" => "User logged in successfully.",
                "data" => [
                    "token" => $auth->encode($request->request->get("user_id"))
                ]
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User not found."
            ], 404);
        }
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
