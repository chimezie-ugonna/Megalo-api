<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IncomingDataValidation
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
        if ($request->isMethod("post")) {
            if ($request->path() == "api/v1/user/send_otp") {
                $request->validate([
                    "phone_number" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/verify_otp") {
                $request->validate([
                    "phone_number" => ["bail", "required", "not_in:null"],
                    "otp" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/create") {
                $request->validate([
                    "user_id" => ["bail", "prohibited"],
                    "phone_number" => ["bail", "required", "not_in:null"],
                    "full_name" => ["bail", "required", "not_in:null"],
                    "dob" => ["bail", "required", "date_format:d/m/Y", "not_in:null"],
                    "email" => ["bail", "required", "email", "not_in:null"],
                    "type" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/login/create") {
                $request->validate([
                    "phone_number" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/investment/create") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "share" => ["bail", "required", "numeric", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/create") {
                $request->validate([
                    "property_id" => ["bail", "prohibited"],
                    "address" => ["bail", "required", "not_in:null"],
                    "value_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "image_strings" => ["bail", "required", "not_in:null"],
                    "available_shares" => ["bail", "required", "numeric", "not_in:null"],
                    "size_sf" => ["bail", "required", "integer", "not_in:null"],
                    "dividend_ps_usd" => ["bail", "required", "numeric", "not_in:null"]
                ]);
            }
        } else if ($request->isMethod("put") || $request->isMethod("patch")) {
            if ($request->path() == "api/v1/investment/update") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "share" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/update") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
                if (sizeof($request->all()) == 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (sizeof($request->all()) > 1) {
                    if (!$request->request->has("address") && !$request->request->has("value") && !$request->request->has("pictures") && !$request->request->has("available_shares") && !$request->request->has("size") && !$request->request->has("dividend")) {
                        return response()->json([
                            "status" => false,
                            "message" => "You provided an invalid key."
                        ], 400)->throwResponse();
                    }
                }
            } else if ($request->path() == "api/v1/user/update") {
                if (sizeof($request->all()) == 0) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("phone_number") && !$request->request->has("full_name") && !$request->request->has("dob") && !$request->request->has("email") && !$request->request->has("type")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("phone_number") && !$request->filled("full_name") && !$request->filled("dob") && !$request->filled("email") && !$request->filled("type")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                }
            }
        } else if ($request->isMethod("get")) {
            if ($request->path() == "api/v1/investment/read_specific") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/read") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            }
        } else if ($request->isMethod("delete")) {
            if ($request->path() == "api/v1/investment/delete") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/delete") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            }
        }
        return $next($request);
    }
}
