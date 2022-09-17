<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Custom\Authentication;
use App\Models\User;
use App\Models\Login;

class TokenValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->path() != "api/v1/user/send_otp" && $request->path() != "api/v1/user/verify_otp") {
            if ($request->bearerToken() != "") {
                $auth = new Authentication();
                $data = $auth->decode($request->bearerToken());
                if ($data != false && isset($data["data"])) {
                    $user_id = $data["data"];
                    if ($request->path() == "api/v1/user/create" || $request->path() == "api/v1/login/create") {
                        if ($request->request->get("phone_number") == $data["data"]) {
                            if (User::where("phone_number", $data["data"])->exists()) {
                                $user_id = User::where("phone_number", $data["data"])->value("user_id");
                            } else {
                                $user_id = uniqid(rand(), true);
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "Unauthorized access, unknown user."
                            ], 401);
                        }
                        $request->request->add([
                            "access_type" => $request->header("access-type"),
                            "device_os" => $request->header("device-os", ""),
                            "device_token" => $request->header("device-token", ""),
                            "device_brand" => $request->header("device-brand", ""),
                            "device_model" => $request->header("device-model", ""),
                            "app_version" => $request->header("app-version", ""),
                            "os_version" => $request->header("os-version", "")
                        ]);
                    } else {
                        if (User::find($user_id)) {
                            Login::where("user_id", $user_id)->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->update([
                                "device_brand" => $request->header("device-brand", ""),
                                "device_model" => $request->header("device-model", ""),
                                "app_version" => $request->header("app-version", ""),
                                "os_version" => $request->header("os-version", "")
                            ]);
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "Unauthorized access, unknown user."
                            ], 401);
                        }
                    }
                    $request->request->add(["user_id" => $user_id]);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "The bearer token is invalid."
                    ], 401)->throwResponse();
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "The bearer token authorization header is missing."
                ], 401)->throwResponse();
            }
        }
        return $next($request);
    }
}
