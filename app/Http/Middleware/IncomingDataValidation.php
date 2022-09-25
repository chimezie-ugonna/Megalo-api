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
                    "phone_number" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/verify_otp") {
                $request->validate([
                    "phone_number" => ["bail", "required", "filled", "not_in:null"],
                    "otp" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/create") {
                $request->validate([
                    "user_id" => ["bail", "prohibited"],
                    "phone_number" => ["bail", "required", "filled", "not_in:null"],
                    "full_name" => ["bail", "required", "filled", "not_in:null"],
                    "dob" => ["bail", "required", "filled", "date_format:d/m/Y", "not_in:null"],
                    "email" => ["bail", "required", "filled", "email", "not_in:null"],
                    "type" => ["bail", "required", "filled", "in:user,admin", "not_in:null"]
                ]);
                if ($request->request->has("full_name") && $request->filled("full_name")) {
                    $full_name_split = explode(" ", $request->request->get("full_name"), 2);
                    $first_name = $full_name_split[0];
                    $last_name = "";
                    if (count($full_name_split) > 1) {
                        $last_name = $full_name_split[1];
                    }
                    $request->request->add([
                        "first_name" => $first_name,
                        "last_name" => $last_name
                    ]);
                    $request->request->remove("full_name");
                }
            } else if ($request->path() == "api/v1/login/create") {
                $request->validate([
                    "phone_number" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/investment/create") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"],
                    "amount_usd" => ["bail", "required", "filled", "numeric", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/create") {
                $request->validate([
                    "property_id" => ["bail", "prohibited"],
                    "address" => ["bail", "required", "filled", "not_in:null"],
                    "value_usd" => ["bail", "required", "filled", "numeric", "not_in:null"],
                    "image_strings" => ["bail", "required", "filled", "not_in:null"],
                    "percentage_available" => ["bail", "required", "filled", "numeric", "not_in:null"],
                    "size_sf" => ["bail", "required", "filled", "numeric", "not_in:null"],
                    "dividend_usd" => ["bail", "required", "filled", "numeric", "not_in:null"]
                ]);
            }
        } else if ($request->isMethod("put") || $request->isMethod("patch")) {
            if ($request->path() == "api/v1/investment/update") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"],
                    "amount_usd" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/update") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"]
                ]);
                if (sizeof($request->all()) <= 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("address") && !$request->request->has("value_usd") && !$request->request->has("image_strings") && !$request->request->has("percentage_available") && !$request->request->has("size_sf") && !$request->request->has("dividend_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("address") && !$request->filled("value_usd") && !$request->filled("image_strings") && !$request->filled("percentage_available") && !$request->filled("size_sf") && !$request->filled("dividend_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                } else {
                    if ($request->request->has("address")) {
                        if ($request->filled("address")) {
                            $request->validate([
                                "address" => ["bail", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("address");
                        }
                    }
                    if ($request->request->has("value_usd")) {
                        if ($request->filled("value_usd")) {
                            $request->validate([
                                "value_usd" => ["bail", "numeric", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("value_usd");
                        }
                    }
                    if ($request->request->has("image_strings")) {
                        if ($request->filled("image_strings")) {
                            $request->validate([
                                "image_strings" => ["bail", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("image_strings");
                        }
                    }
                    if ($request->request->has("percentage_available")) {
                        if ($request->filled("percentage_available")) {
                            $request->validate([
                                "percentage_available" => ["bail", "numeric", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("percentage_available");
                        }
                    }
                    if ($request->request->has("size_sf")) {
                        if ($request->filled("size_sf")) {
                            $request->validate([
                                "size_sf" => ["bail", "numeric", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("size_sf");
                        }
                    }
                    if ($request->request->has("dividend_usd")) {
                        if ($request->filled("dividend_usd")) {
                            $request->validate([
                                "dividend_usd" => ["bail", "numeric", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("dividend_usd");
                        }
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
                } else {
                    if ($request->request->has("phone_number")) {
                        if ($request->filled("phone_number")) {
                            $request->validate([
                                "phone_number" => ["bail", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("phone_number");
                        }
                    }
                    if ($request->request->has("full_name")) {
                        if ($request->filled("full_name")) {
                            $request->validate([
                                "full_name" => ["bail", "not_in:null"]
                            ]);
                            $full_name_split = explode(" ", $request->request->get("full_name"), 2);
                            $first_name = $full_name_split[0];
                            $last_name = "";
                            if (count($full_name_split) > 1) {
                                $last_name = $full_name_split[1];
                            }
                            $request->request->add([
                                "first_name" => $first_name,
                                "last_name" => $last_name
                            ]);
                        }
                        $request->request->remove("full_name");
                    }
                    if ($request->request->has("dob")) {
                        if ($request->filled("dob")) {
                            $request->validate([
                                "dob" => ["bail", "date_format:d/m/Y", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("dob");
                        }
                    }
                    if ($request->request->has("email")) {
                        if ($request->filled("email")) {
                            $request->validate([
                                "email" => ["bail", "email", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("email");
                        }
                    }
                    if ($request->request->has("type")) {
                        if ($request->filled("type")) {
                            $request->validate([
                                "type" => ["bail", "in:user,admin", "not_in:null"]
                            ]);
                        } else {
                            $request->request->remove("type");
                        }
                    }
                }
            }
        } else if ($request->isMethod("get")) {
            if ($request->path() == "api/v1/investment/read_specific") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/read") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"]
                ]);
            }
        } else if ($request->isMethod("delete")) {
            if ($request->path() == "api/v1/investment/delete") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/delete") {
                $request->validate([
                    "property_id" => ["bail", "required", "filled", "not_in:null"]
                ]);
            }
        }
        return $next($request);
    }
}
