<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHeader
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
        if ($request->path() != "api/v1/user/verify_identity_webhook" && $request->path() != "api/v1/user/redirect_app_download") {
            if ($request->hasHeader("Accept") == null || $request->header("Accept") != "application/json") {
                return response()->json([
                    "status" => false,
                    "message" => "Missing Accept 'application/json' header."
                ], 400)->throwResponse();
            } else if ($request->hasHeader("access-type") == null || $request->header("access-type") == "") {
                return response()->json([
                    "status" => false,
                    "message" => "The access-type header is missing."
                ], 400)->throwResponse();
            } else if ($request->header("access-type") != "" && $request->header("access-type") != "mobile" && $request->header("access-type") != "web" && $request->header("access-type") != "postman") {
                return response()->json([
                    "status" => false,
                    "message" => "Invalid value in the access-type header. Value can only be 'mobile', 'web' or 'postman'."
                ], 400)->throwResponse();
            } else if ($request->header("access-type") == "mobile") {
                if ($request->hasHeader("device-os") == null || $request->header("device-os") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-os header is missing."
                    ], 400)->throwResponse();
                } else if ($request->header("device-os") != "" && $request->header("device-os") != "android" && $request->header("device-os") != "ios") {
                    return response()->json([
                        "status" => false,
                        "message" => "Invalid value in the device-os header. Value can only be 'android' or 'ios'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-token") == null || $request->header("device-token") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-token header is missing."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-brand") == null || $request->header("device-brand") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-brand header is missing."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-model") == null || $request->header("device-model") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-model header is missing."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("app-version") == null || $request->header("app-version") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The app-version header is missing."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("app-language-code") == null || $request->header("app-language-code") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The app-language-code header is missing."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("os-version") == null || $request->header("os-version") == "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The os-version header is missing."
                    ], 400)->throwResponse();
                }
            } else {
                if ($request->hasHeader("device-os") != null && $request->header("device-os") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-os header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-token") != null && $request->header("device-token") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-token header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-brand") != null && $request->header("device-brand") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-brand header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("device-model") != null && $request->header("device-model") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The device-model header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("app-version") != null && $request->header("app-version") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The app-version header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("app-language-code") != null && $request->header("app-language-code") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The app-language-code header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                } else if ($request->hasHeader("os-version") != null && $request->header("os-version") != "") {
                    return response()->json([
                        "status" => false,
                        "message" => "The os-version header should be blank since the access-type header is not 'mobile'."
                    ], 400)->throwResponse();
                }
            }
        }
        return $next($request);
    }
}
