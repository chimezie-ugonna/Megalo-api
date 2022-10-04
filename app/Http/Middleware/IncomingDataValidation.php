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
                    "phone_number" => ["bail", "required", "not_in:null"],
                    "full_name" => ["bail", "required", "not_in:null"],
                    "dob" => ["bail", "required", "date_format:d/m/Y", "not_in:null"],
                    "email" => ["bail", "required", "email", "not_in:null"],
                    "balance_usd" => ["bail", "prohibited"],
                    "is_admin" => ["bail", "prohibited"],
                    "email_verified" => ["bail", "prohibited"],
                    "identity_verified" => ["bail", "prohibited"],
                    "referral_code" => ["bail", "not_in:null"]
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
                    "phone_number" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/investment/create") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "amount_paid_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/create") {
                $request->validate([
                    "address" => ["bail", "required", "not_in:null"],
                    "value_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "image_urls" => ["bail", "required", "not_in:null"],
                    "percentage_available" => ["bail", "required", "numeric", "not_in:null"],
                    "size_sf" => ["bail", "required", "numeric", "not_in:null"],
                    "monthly_earning_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "monthly_dividend_usd" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/pay_dividend") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "amount_usd" => ["bail", "prohibited"],
                    "investor_count" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/payment/create") {
                $request->validate([
                    "type" => ["bail", "required", "in:deposit,withdrawal"],
                    "reference" => ["bail", "prohibited"],
                    "amount_usd" => ["bail", "required", "numeric", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/notification/create") {
                $request->validate([
                    "seen" => ["bail", "prohibited"],
                    "tappable" => ["bail", "in:true,false"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "in:property"],
                    "receiver_user_id" => ["bail", "required", "not_in:null"],
                    "title" => ["bail", "required", "not_in:null"],
                    "body" => ["bail", "required", "not_in:null"]
                ]);

                if ($request->request->has("sender_user_id") && !$request->filled("sender_user_id")) {
                    $request->request->remove("sender_user_id");
                }
                if ($request->request->has("tappable") && $request->filled("tappable") && $request->request->get("tappable") == "true") {
                    if (!$request->request->has("redirection_page") || !$request->filled("redirection_page")) {
                        return response()->json([
                            "status" => false,
                            "message" => "A 'redirection_page' value is required if 'tappable' value is 'true'."
                        ], 400)->throwResponse();
                    } else if (!$request->request->has("redirection_page_id") || !$request->filled("redirection_page_id")) {
                        return response()->json([
                            "status" => false,
                            "message" => "A 'redirection_page_id' value is required if 'tappable' value is 'true'."
                        ], 400)->throwResponse();
                    }
                } else {
                    if ($request->request->has("redirection_page")) {
                        return response()->json([
                            "status" => false,
                            "message" => "The 'redirection_page' field is not required if 'tappable' value is not 'true'."
                        ], 400)->throwResponse();
                    } else if ($request->request->has("redirection_page_id")) {
                        return response()->json([
                            "status" => false,
                            "message" => "The 'redirection_page_id' field is not required if 'tappable' value is not 'true'."
                        ], 400)->throwResponse();
                    }
                }
            } else if ($request->path() == "api/v1/notification/create_all") {
                $request->validate([
                    "seen" => ["bail", "prohibited"],
                    "tappable" => ["bail", "in:true,false"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "in:property"],
                    "sender_user_id" => ["bail", "prohibited"],
                    "receiver_user_id" => ["bail", "prohibited"],
                    "title" => ["bail", "required", "not_in:null"],
                    "body" => ["bail", "required", "not_in:null"]
                ]);

                if ($request->request->has("tappable") && $request->filled("tappable") && $request->request->get("tappable") == "true") {
                    if (!$request->request->has("redirection_page") || !$request->filled("redirection_page")) {
                        return response()->json([
                            "status" => false,
                            "message" => "A 'redirection_page' value is required if 'tappable' value is 'true'."
                        ], 400)->throwResponse();
                    } else if (!$request->request->has("redirection_page_id") || !$request->filled("redirection_page_id")) {
                        return response()->json([
                            "status" => false,
                            "message" => "A 'redirection_page_id' value is required if 'tappable' value is 'true'."
                        ], 400)->throwResponse();
                    }
                } else {
                    if ($request->request->has("redirection_page")) {
                        return response()->json([
                            "status" => false,
                            "message" => "The 'redirection_page' field is not required if 'tappable' value is not 'true'."
                        ], 400)->throwResponse();
                    } else if ($request->request->has("redirection_page_id")) {
                        return response()->json([
                            "status" => false,
                            "message" => "The 'redirection_page_id' field is not required if 'tappable' value is not 'true'."
                        ], 400)->throwResponse();
                    }
                }
            }
        } else if ($request->isMethod("put") || $request->isMethod("patch")) {
            if ($request->path() == "api/v1/property/update") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "percentage_available" => ["bail", "prohibited"],
                    "value_usd" => ["bail", "numeric", "not_in:null"],
                    "address" => ["bail", "not_in:null"],
                    "image_urls" => ["bail", "not_in:null"],
                    "size_sf" => ["bail", "numeric", "not_in:null"],
                    "monthly_earning_usd" => ["bail", "numeric", "not_in:null"],
                    "monthly_dividend_usd" => ["bail", "prohibited"]
                ]);
                if (sizeof($request->all()) <= 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("address") && !$request->request->has("value_usd") && !$request->request->has("image_urls") && !$request->request->has("size_sf") && !$request->request->has("monthly_earning_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("address") && !$request->filled("value_usd") && !$request->filled("image_urls") && !$request->filled("size_sf") && !$request->filled("monthly_earning_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                } else {
                    if ($request->request->has("address") && !$request->filled("address")) {
                        $request->request->remove("address");
                    }
                    if ($request->request->has("value_usd") && !$request->filled("value_usd")) {
                        $request->request->remove("value_usd");
                    }
                    if ($request->request->has("image_urls") && !$request->filled("image_urls")) {
                        $request->request->remove("image_urls");
                    }
                    if ($request->request->has("size_sf") && !$request->filled("size_sf")) {
                        $request->request->remove("size_sf");
                    }
                    if ($request->request->has("monthly_earning_usd") && !$request->filled("monthly_earning_usd")) {
                        $request->request->remove("monthly_earning_usd");
                    }
                }
            } else if ($request->path() == "api/v1/user/update") {
                $request->validate([
                    "balance_usd" => ["bail", "prohibited"],
                    "is_admin" => ["bail", "prohibited"],
                    "email_verified" => ["bail", "in:true,false"],
                    "identity_verified" => ["bail", "in:true,false"],
                    "phone_number" => ["bail", "not_in:null"],
                    "full_name" => ["bail", "not_in:null"],
                    "dob" => ["bail", "date_format:d/m/Y", "not_in:null"],
                    "email" => ["bail", "email", "not_in:null"],
                    "referral_code" => ["bail", "prohibited"]
                ]);
                if (sizeof($request->all()) == 0) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("phone_number") && !$request->request->has("full_name") && !$request->request->has("dob") && !$request->request->has("email") && !$request->request->has("email_verified") && !$request->request->has("identity_verified")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("phone_number") && !$request->filled("full_name") && !$request->filled("dob") && !$request->filled("email") && !$request->filled("email_verified") && !$request->filled("identity_verified")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                } else {
                    if ($request->request->has("phone_number") && !$request->filled("phone_number")) {
                        $request->request->remove("phone_number");
                    }
                    if ($request->request->has("full_name")) {
                        if ($request->filled("full_name")) {
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
                    if ($request->request->has("dob") && !$request->filled("dob")) {
                        $request->request->remove("dob");
                    }
                    if ($request->request->has("email") && !$request->filled("email")) {
                        $request->request->remove("email");
                    }
                    if ($request->request->has("email_verified") && !$request->filled("email_verified")) {
                        $request->request->remove("email_verified");
                    }
                    if ($request->request->has("identity_verified") && !$request->filled("identity_verified")) {
                        $request->request->remove("identity_verified");
                    }
                }
            } else if ($request->path() == "api/v1/investment/liquidate") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "amount_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/notification/update") {
                $request->validate([
                    "notification_id" => ["bail", "required", "not_in:null"],
                    "seen" => ["bail", "in:true,false"],
                    "tappable" => ["bail", "prohibited"],
                    "tapped" => ["bail", "in:true,false"],
                    "redirection_page" => ["bail", "prohibited"],
                    "redirection_page_id" => ["bail", "prohibited"],
                    "sender_user_id" => ["bail", "prohibited"],
                    "receiver_user_id" => ["bail", "prohibited"],
                    "title" => ["bail", "prohibited"],
                    "body" => ["bail", "prohibited"]
                ]);

                if (sizeof($request->all()) <= 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("seen") && !$request->request->has("tapped")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("seen") && !$request->filled("tapped")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                } else {
                    if ($request->request->has("seen") && !$request->filled("seen")) {
                        $request->request->remove("seen");
                    }
                    if ($request->request->has("tapped") && !$request->filled("tapped")) {
                        $request->request->remove("tapped");
                    }
                }
            }
        } else if ($request->isMethod("get")) {
            if ($request->path() == "api/v1/investment/read_user_and_property_specific" || $request->path() == "api/v1/investment/read_property_specific") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/read") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/property/read_paid_dividend") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/payment/read" || $request->path() == "api/v1/payment/read_user_and_payment_specific") {
                $request->validate([
                    "payment_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/notification/read") {
                $request->validate([
                    "notification_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/read_earning") {
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
            } else if ($request->path() == "api/v1/payment/delete") {
                $request->validate([
                    "payment_id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/notification/delete") {
                $request->validate([
                    "notification_id" => ["bail", "required", "not_in:null"]
                ]);
            }
        }
        return $next($request);
    }
}
