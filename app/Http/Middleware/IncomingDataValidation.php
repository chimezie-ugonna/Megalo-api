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
                    "referral_code" => ["bail", "not_in:null", "filled"],
                    "payment_customer_id" => ["bail", "prohibited"],
                    "payment_account_id" => ["bail", "prohibited"]
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
            } else if ($request->path() == "api/v1/user/create_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "type" => ["bail", "required", "in:card,bank_account"]
                ]);
                if ($request->request->has("type") && $request->filled("type") && $request->request->get("type") == "card") {
                    $request->validate([
                        "number" => ["bail", "required", "numeric", "not_in:null"],
                        "exp_month" => ["bail", "required", "numeric", "not_in:null", "min:2"],
                        "exp_year" => ["bail", "required", "numeric", "not_in:null", "min:2", "max:4"],
                        "cvc" => ["bail", "required", "numeric", "not_in:null"],
                        "country" => ["bail", "prohibited"],
                        "currency" => ["bail", "prohibited"],
                        "account_number" => ["bail", "prohibited"]
                    ]);
                } else if ($request->request->has("type") && $request->filled("type") && $request->request->get("type") == "bank_account") {
                    $request->validate([
                        "country" => ["bail", "required", "alpha", "not_in:null", "min:2", "max:2"],
                        "currency" => ["bail", "required", "alpha", "not_in:null", "min:3", "max:3"],
                        "account_number" => ["bail", "required", "numeric", "not_in:null"],
                        "number" => ["bail", "prohibited"],
                        "exp_month" => ["bail", "prohibited"],
                        "exp_year" => ["bail", "prohibited"],
                        "cvc" => ["bail", "prohibited"]
                    ]);
                }
            } else if ($request->path() == "api/v1/login/create") {
                $request->validate([
                    "phone_number" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/investment/create") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "amount_invested_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/create") {
                $request->validate([
                    "address" => ["bail", "required", "not_in:null"],
                    "value_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "image_urls" => ["bail", "required", "not_in:null"],
                    "description" => ["bail", "required", "not_in:null"],
                    "percentage_available" => ["bail", "prohibited"],
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
                    "tappable" => ["bail", "in:true,false", "filled"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "in:balance,earning,property", "filled"],
                    "redirection_page_id" => ["bail", "not_in:null", "filled"],
                    "sender_user_id" => ["bail", "not_in:null", "filled"],
                    "receiver_user_id" => ["bail", "required", "not_in:null"],
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
            } else if ($request->path() == "api/v1/notification/create_all") {
                $request->validate([
                    "seen" => ["bail", "prohibited"],
                    "tappable" => ["bail", "in:true,false", "filled"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "in:balance,earning,property", "filled"],
                    "redirection_page_id" => ["bail", "not_in:null", "filled"],
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
                    "value_usd" => ["bail", "numeric", "not_in:null", "filled"],
                    "address" => ["bail", "not_in:null", "filled"],
                    "image_urls" => ["bail", "not_in:null", "filled"],
                    "description" => ["bail", "not_in:null", "filled"],
                    "size_sf" => ["bail", "numeric", "not_in:null", "filled"],
                    "monthly_earning_usd" => ["bail", "numeric", "not_in:null", "filled"],
                    "monthly_dividend_usd" => ["bail", "prohibited"]
                ]);
                if (sizeof($request->all()) <= 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("address") && !$request->request->has("value_usd") && !$request->request->has("image_urls") && !$request->request->has("description") && !$request->request->has("size_sf") && !$request->request->has("monthly_earning_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("address") && !$request->filled("value_usd") && !$request->filled("image_urls") && !$request->filled("description") && !$request->filled("size_sf") && !$request->filled("monthly_earning_usd")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                }
            } else if ($request->path() == "api/v1/user/update") {
                $request->validate([
                    "balance_usd" => ["bail", "prohibited"],
                    "is_admin" => ["bail", "prohibited"],
                    "email_verified" => ["bail", "in:true,false", "filled"],
                    "identity_verified" => ["bail", "in:true,false", "filled"],
                    "phone_number" => ["bail", "not_in:null", "filled"],
                    "full_name" => ["bail", "not_in:null", "filled"],
                    "dob" => ["bail", "date_format:d/m/Y", "not_in:null", "filled"],
                    "email" => ["bail", "email", "not_in:null", "filled"],
                    "referral_code" => ["bail", "prohibited"],
                    "payment_customer_id" => ["bail", "prohibited"],
                    "payment_account_id" => ["bail", "prohibited"]
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
                    if ($request->request->has("email") && $request->filled("email")) {
                        if ($request->request->has("email_verified")) {
                            $request->request->set("email_verified", false);
                        } else {
                            $request->request->add(["email_verified" => false]);
                        }
                    }
                }
            } else if ($request->path() == "api/v1/user/update_default_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/investment/liquidate") {
                $request->validate([
                    "property_id" => ["bail", "required", "not_in:null"],
                    "amount_usd" => ["bail", "required", "numeric", "not_in:null"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/notification/update") {
                $request->validate([
                    "notification_id" => ["bail", "required", "not_in:null"],
                    "seen" => ["bail", "in:true,false", "filled"],
                    "tappable" => ["bail", "prohibited"],
                    "tapped" => ["bail", "in:true,false", "filled"],
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
            } else if ($request->path() == "api/v1/user/read_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/read_all_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "type" => ["bail", "required", "in:card,bank_account"]
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
            } else if ($request->path() == "api/v1/user/delete_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required", "not_in:null"]
                ]);
            }
        }
        return $next($request);
    }
}
