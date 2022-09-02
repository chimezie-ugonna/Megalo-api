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
        if ($request->isMethod("put") || $request->isMethod("patch")) {
            if (sizeof($request->all()) == 0) {
                return response()->json([
                    "status" => false,
                    "message" => "There is nothing to update."
                ], 400)->throwResponse();
            } else {
                $request->validate([
                    "user_id" => ["bail", "prohibited"],
                    "phone_number" => ["bail", "prohibited"],
                    "full_name" => ["bail", "not_in:null"],
                    "email" => ["bail", "email", "not_in:null"],
                    "gender" => ["bail", "not_in:null"],
                    "dob" => ["bail", "not_in:null"]
                ]);
            }
        } else if ($request->isMethod("post") && $request->path() == "api/v1/user/send_otp") {
            $request->validate([
                "phone_number" => ["bail", "required", "not_in:null"]
            ]);
        } else if ($request->isMethod("post") && $request->path() == "api/v1/user/verify_otp") {
            $request->validate([
                "phone_number" => ["bail", "required", "not_in:null"],
                "otp" => ["bail", "required", "not_in:null"]
            ]);
        } else if ($request->isMethod("post") && $request->path() == "api/v1/user/create") {
            $request->validate([
                "phone_number" => ["bail", "required", "not_in:null"],
                "country_name_code" => ["bail", "required", "not_in:null"],
                "full_name" => ["bail", "required", "not_in:null"],
                "dob" => ["bail", "required", "date_format:d/m/Y", "not_in:null"],
                "email" => ["bail", "required", "email", "not_in:null"],
                "type" => ["bail", "required", "not_in:null"]
            ]);
        } else if ($request->isMethod("post") && $request->path() == "api/v1/login/create") {
            $request->validate([
                "phone_number" => ["bail", "required", "not_in:null"]
            ]);
        }
        return $next($request);
    }
}
