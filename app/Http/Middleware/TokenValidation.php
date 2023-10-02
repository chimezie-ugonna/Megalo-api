<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Custom\Authentication;
use App\Models\FailedWithdrawal;
use App\Models\User;
use App\Models\Login;
use App\Models\Payment;
use App\Models\Property;

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
        if ($request->path() != "api/v1/user/send_otp" && $request->path() != "api/v1/user/verify_otp" && $request->path() != "api/v1/user/verify_identity_webhook" && $request->path() != "api/v1/user/redirect_app_download" || $request->path() == "api/v1/user/send_otp" && $request->request->has("update") && $request->filled("update") && $request->request->get("update") || $request->path() == "api/v1/user/verify_otp" && $request->request->has("update") && $request->filled("update") && $request->request->get("update") || $request->path() == "api/v1/user/send_otp" && $request->request->has("type") && $request->filled("type") && $request->request->get("type") == "email" || $request->path() == "api/v1/user/verify_otp" && $request->request->has("type") && $request->filled("type") && $request->request->get("type") == "email") {
            if ($request->bearerToken() != "") {
                $auth = new Authentication();
                $data = $auth->decode($request->bearerToken());
                if (isset($data) && isset($data["data"])) {
                    $ip_address = $this->getIpAddress();

                    $user_id = $data["data"];
                    if ($request->path() == "api/v1/user/create" || $request->path() == "api/v1/login/create") {
                        if ($request->request->get("phone_number") == $data["data"]) {
                            if (User::where("phone_number", $data["data"])->exists()) {
                                $user_id = User::where("phone_number", $data["data"])->value("user_id");
                            } else {
                                do {
                                    $user_id = uniqid(rand(), true);
                                } while (User::where("user_id", $user_id)->exists());
                            }

                            $request->request->add([
                                "access_type" => $request->header("access-type"),
                                "device_os" => $request->header("device-os", ""),
                                "device_token" => $request->header("device-token", ""),
                                "device_brand" => $request->header("device-brand", ""),
                                "device_model" => $request->header("device-model", ""),
                                "app_version" => $request->header("app-version", ""),
                                "app_language_code" => $request->header("app-language-code", ""),
                                "os_version" => $request->header("os-version", ""),
                                "ip_address" => $ip_address,
                                "updated_at" => now()
                            ]);
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "Unauthorized access, unknown user."
                            ], 401);
                        }
                    } else {
                        if (User::where("user_id", $user_id)->exists()) {
                            if (!Login::where("user_id", $user_id)->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->exists()) {
                                return response()->json([
                                    "status" => false,
                                    "message" => "User not logged in. User needs to be logged in to access this endpoint."
                                ], 420);
                            }

                            $admin_only_endpoints = [
                                "api/v1/user/read_all",
                                "api/v1/user/read_specific",
                                "api/v1/user/read_all_earning",
                                "api/v1/user/read_dashboard_data",
                                "api/v1/login/read",
                                "api/v1/login/read_all",
                                "api/v1/property/create",
                                "api/v1/property/pay_dividend",
                                "api/v1/property/read_paid_dividend",
                                "api/v1/property/update",
                                "api/v1/property/delete",
                                "api/v1/investment/read_all",
                                "api/v1/payment/read_all",
                                "api/v1/notification/create",
                                "api/v1/notification/create_all",
                                "api/v1/notification/read_all",
                                "api/v1/notification/read_all_key"
                            ];
                            if (in_array($request->path(), $admin_only_endpoints)) {
                                if (!User::where("user_id", $user_id)->value("is_admin")) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "Unauthorized access, only admins can access this endpoint."
                                    ], 403);
                                }

                                if ($request->header("access-type") != "" && $request->header("access-type") != "web" && $request->header("access-type") != "postman") {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "Unauthorized access, this endpoint can not be accessed with the provided access-type header."
                                    ], 403);
                                }
                            }

                            switch ($request->path()) {
                                case "api/v1/property/create":
                                    do {
                                        $property_id = uniqid(rand(), true);
                                    } while (Property::where("property_id", $property_id)->exists());
                                    $request->request->add(["property_id" => $property_id]);

                                    break;
                                case "api/v1/payment/create":
                                    do {
                                        $payment_id = uniqid(rand(), true);
                                    } while (Payment::where("payment_id", $payment_id)->exists() || FailedWithdrawal::where("payment_id", $payment_id)->exists());
                                    $request->request->add(["payment_id" => $payment_id]);

                                    break;
                            }

                            if ($request->path() != "api/v1/login/update_device_token") {
                                Login::where("user_id", $user_id)->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->update([
                                    "device_brand" => $request->header("device-brand", ""),
                                    "device_model" => $request->header("device-model", ""),
                                    "app_version" => $request->header("app-version", ""),
                                    "app_language_code" => $request->header("app-language-code", ""),
                                    "os_version" => $request->header("os-version", ""),
                                    "ip_address" => $ip_address,
                                    "updated_at" => now()
                                ]);
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "This user does not exist."
                            ], 420);
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

    function getIpAddress()
    {
        $ipaddress = "";
        if (isset($_SERVER["HTTP_CLIENT_IP"]))
            $ipaddress = $_SERVER["HTTP_CLIENT_IP"];
        else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $ipaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if (isset($_SERVER["HTTP_X_FORWARDED"]))
            $ipaddress = $_SERVER["HTTP_X_FORWARDED"];
        else if (isset($_SERVER["HTTP_FORWARDED_FOR"]))
            $ipaddress = $_SERVER["HTTP_FORWARDED_FOR"];
        else if (isset($_SERVER["HTTP_FORWARDED"]))
            $ipaddress = $_SERVER["HTTP_FORWARDED"];
        else if (isset($_SERVER["REMOTE_ADDR"]))
            $ipaddress = $_SERVER["REMOTE_ADDR"];
        else
            $ipaddress = "Unknown";
        return $ipaddress;
    }
}
