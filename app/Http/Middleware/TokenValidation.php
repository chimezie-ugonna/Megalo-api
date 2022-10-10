<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Custom\Authentication;
use App\Models\User;
use App\Models\Login;
use App\Models\Notification;
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
                                do {
                                    $user_id = uniqid(rand(), true);
                                } while (User::find($user_id));
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "Unauthorized access, unknown user."
                            ], 401);
                        }
                        if ($request->request->has("access_type")) {
                            $request->request->remove("access_type");
                        }
                        if ($request->request->has("device_os")) {
                            $request->request->remove("device_os");
                        }
                        if ($request->request->has("device_token")) {
                            $request->request->remove("device_token");
                        }
                        if ($request->request->has("device_brand")) {
                            $request->request->remove("device_brand");
                        }
                        if ($request->request->has("device_model")) {
                            $request->request->remove("device_model");
                        }
                        if ($request->request->has("app_version")) {
                            $request->request->remove("app_version");
                        }
                        if ($request->request->has("os_version")) {
                            $request->request->remove("os_version");
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
                            if (sizeof(Login::where("user_id", $user_id)->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->get()) == 0) {
                                return response()->json([
                                    "status" => false,
                                    "message" => "User not logged in. User needs to be logged in to access this endpoint."
                                ], 401);
                            }

                            if ($request->path() == "api/v1/user/read_all" || $request->path() == "api/v1/user/read_all_earning" || $request->path() == "api/v1/login/read" || $request->path() == "api/v1/login/read_all" || $request->path() == "api/v1/property/create" || $request->path() == "api/v1/property/pay_dividend" || $request->path() == "api/v1/property/read_all" || $request->path() == "api/v1/property/read_paid_dividend" || $request->path() == "api/v1/property/update" || $request->path() == "api/v1/property/delete" || $request->path() == "api/v1/investment/read_all" || $request->path() == "api/v1/payment/read_all" || $request->path() == "api/v1/notification/create" || $request->path() == "api/v1/notification/create_all" || $request->path() == "api/v1/notification/read_all") {
                                if (User::find($user_id)->value("is_admin") === false) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "Unauthorized access, only admins can access this endpoint."
                                    ], 401);
                                }
                            }

                            switch ($request->path()) {
                                case "api/v1/property/create":
                                    if ($request->request->has("property_id")) {
                                        $request->request->remove("property_id");
                                    }

                                    do {
                                        $property_id = uniqid(rand(), true);
                                    } while (Property::find($property_id));
                                    $request->request->add(["property_id" => $property_id]);

                                    break;
                                case "api/v1/payment/create":
                                    if ($request->request->has("payment_id")) {
                                        $request->request->remove("payment_id");
                                    }

                                    do {
                                        $payment_id = uniqid(rand(), true);
                                    } while (Payment::find($payment_id));
                                    $request->request->add(["payment_id" => $payment_id]);

                                    break;
                                case "api/v1/notification/create":
                                    if ($request->request->has("notification_id")) {
                                        $request->request->remove("notification_id");
                                    }

                                    do {
                                        $notification_id = uniqid(rand(), true);
                                    } while (Notification::find($notification_id));
                                    $request->request->add(["notification_id" => $notification_id]);

                                    break;
                            }

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
                    if ($request->request->has("user_id")) {
                        $request->request->remove("user_id");
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
