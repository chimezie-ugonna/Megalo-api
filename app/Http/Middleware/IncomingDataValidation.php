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
                    "type" => ["bail", "required", "in:email,sms"],
                    "email" => ["bail", "prohibited_if:type,sms", "filled", "email", "required_if:type,email"],
                    "phone_number" => ["bail", "prohibited_if:type,email", "filled", "required_if:type,sms"],
                    "update" => ["bail", "prohibited_if:type,email", "filled", "boolean"]
                ]);
            } else if ($request->path() == "api/v1/user/verify_otp") {
                $request->validate([
                    "type" => ["bail", "required", "in:email,sms"],
                    "email" => ["bail", "prohibited_if:type,sms", "filled", "email", "required_if:type,email"],
                    "phone_number" => ["bail", "prohibited_if:type,email", "filled", "required_if:type,sms"],
                    "otp" => ["bail", "required"],
                    "update" => ["bail", "prohibited_if:type,email", "filled", "boolean"]
                ]);
            } else if ($request->path() == "api/v1/user/create") {
                $request->validate([
                    "phone_number" => ["bail", "required"],
                    "full_name" => ["bail", "required"],
                    "dob" => ["bail", "required", "date_format:d/m/Y"],
                    "email" => ["bail", "required", "email"],
                    "balance_usd" => ["bail", "prohibited"],
                    "is_admin" => ["bail", "prohibited"],
                    "email_verified" => ["bail", "prohibited"],
                    "identity_verification_status" => ["bail", "prohibited"],
                    "identity_verification_id" => ["bail", "prohibited"],
                    "referral_code" => ["bail", "filled", "not_in:null"],
                    "payment_customer_id" => ["bail", "prohibited"],
                    "payment_account_id" => ["bail", "prohibited"],
                    "user_id" => ["bail", "prohibited"],
                    "device_token_updated_at" => ["bail", "prohibited"],
                    "nationality" => ["bail", "prohibited"],
                    "image_url" => ["bail", "prohibited"],
                    "gender" => ["bail", "prohibited"]
                ]);
                if ($request->request->has("full_name") && $request->filled("full_name")) {
                    $full_name_split = explode(" ", $request->request->get("full_name"), 2);
                    $first_name = $full_name_split[0];
                    $last_name = "";
                    if (count($full_name_split) > 1) {
                        $last_name = $full_name_split[1];
                    }
                    $request->request->add([
                        "first_name" => ucwords(strtolower($first_name)),
                        "last_name" => ucwords(strtolower($last_name))
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
                        "number" => ["bail", "required", "numeric"],
                        "exp_month" => ["bail", "required", "numeric", "digits:2"],
                        "exp_year" => ["bail", "required", "numeric", "digits:4"],
                        "cvc" => ["bail", "required", "numeric", "digits_between:3,4"],
                        "country" => ["bail", "prohibited"],
                        "currency" => ["bail", "prohibited_if:action,deposit", "filled", "alpha", "size:3", "lowercase", "required_if:action,withdrawal"],
                        "account_holder_name" => ["bail", "prohibited"],
                        "account_holder_type" => ["bail", "prohibited"],
                        "account_number" => ["bail", "prohibited"],
                        "routing_number" => ["bail", "prohibited"]
                    ]);
                } else if ($request->request->has("type") && $request->filled("type") && $request->request->get("type") == "bank_account") {
                    $request->validate([
                        "country" => ["bail", "required", "alpha", "size:2", "uppercase"],
                        "currency" => ["bail", "required", "alpha", "size:3", "lowercase"],
                        "account_holder_name" => ["bail", "required"],
                        "account_holder_type" => ["bail", "required", "in:individual,company"],
                        "account_number" => ["bail", "required", "numeric"],
                        "routing_number" => ["bail", "filled", "numeric", "required_if:country,US"],
                        "number" => ["bail", "prohibited"],
                        "exp_month" => ["bail", "prohibited"],
                        "exp_year" => ["bail", "prohibited"],
                        "cvc" => ["bail", "prohibited"]
                    ]);
                }
            } else if ($request->path() == "api/v1/login/create") {
                $request->validate([
                    "phone_number" => ["bail", "required"],
                    "device_token_updated_at" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/investment/create") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "amount_invested_usd" => ["bail", "required", "numeric", "gte:0.50", "lte:999999.99"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/create") {
                $request->validate([
                    "address" => ["bail", "required"],
                    "value_usd" => ["bail", "required", "numeric"],
                    "image_urls" => ["bail", "required"],
                    "description" => ["bail", "required"],
                    "percentage_available" => ["bail", "required", "numeric"],
                    "size_sf" => ["bail", "required", "numeric"],
                    "monthly_earning_usd" => ["bail", "required", "numeric"],
                    "value_average_annual_change_percentage" => ["bail", "required", "numeric"],
                    "sold" => ["bail", "prohibited"],
                    "company_percentage" => ["bail", "prohibited"],
                    "property_id" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/pay_dividend") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "amount_usd" => ["bail", "prohibited"],
                    "investor_count" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/property/calculate_potential") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "amount_usd" => ["bail", "required", "numeric", "gte:0.50", "lte:999999.99"],
                    "time_period" => ["bail", "required", "numeric"]
                ]);
            } else if ($request->path() == "api/v1/payment/create") {
                $request->validate([
                    "type" => ["bail", "required", "in:deposit,withdrawal"],
                    "reference" => ["bail", "prohibited"],
                    "amount_usd" => ["bail", "required", "numeric", "gte:0.50", "lte:999999.99"],
                    "payment_id" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/notification/create") {
                $request->validate([
                    "seen" => ["bail", "prohibited"],
                    "tappable" => ["bail", "filled", "boolean"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "prohibited_if:tappable,false,0", "filled", "in:balance,earning,property", "required_if:tappable,true,1"],
                    "redirection_page_id" => ["bail", "prohibited_if:tappable,false,0", "filled", "not_in:null", "required_if:tappable,true,1"],
                    "sender_user_id" => ["bail", "prohibited"],
                    "receiver_user_id" => ["bail", "required"],
                    "title_key" => ["bail", "required", "in:test,appreciation_title"],
                    "body_key" => ["bail", "required", "in:test,appreciation_body"],
                    "title" => ["bail", "prohibited"],
                    "body" => ["bail", "prohibited"],
                    "notification_id" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/notification/create_all") {
                $request->validate([
                    "seen" => ["bail", "prohibited"],
                    "tappable" => ["bail", "filled", "boolean"],
                    "tapped" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "prohibited_if:tappable,false,0", "filled", "in:balance,earning,property", "required_if:tappable,true,1"],
                    "redirection_page_id" => ["bail", "prohibited_if:tappable,false,0", "filled", "not_in:null", "required_if:tappable,true,1"],
                    "sender_user_id" => ["bail", "prohibited"],
                    "receiver_user_id" => ["bail", "prohibited"],
                    "title_key" => ["bail", "required", "in:test,appreciation_title"],
                    "body_key" => ["bail", "required", "in:test,appreciation_body"],
                    "title" => ["bail", "prohibited"],
                    "body" => ["bail", "prohibited"],
                    "notification_id" => ["bail", "prohibited"]
                ]);
            }
        } else if ($request->isMethod("put") || $request->isMethod("patch")) {
            if ($request->path() == "api/v1/property/update") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "company_percentage" => ["bail", "prohibited"],
                    "percentage_available" => ["bail", "prohibited"],
                    "value_average_annual_change_percentage" => ["bail", "prohibited"],
                    "value_usd" => ["bail", "filled", "numeric"],
                    "address" => ["bail", "filled", "not_in:null"],
                    "image_urls" => ["bail", "filled", "not_in:null"],
                    "description" => ["bail", "filled", "not_in:null"],
                    "size_sf" => ["bail", "filled", "numeric"],
                    "monthly_earning_usd" => ["bail", "filled", "numeric"],
                    "sold" => ["bail", "filled", "boolean"]
                ]);
                if (sizeof($request->all()) <= 1) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("address") && !$request->request->has("value_usd") && !$request->request->has("image_urls") && !$request->request->has("description") && !$request->request->has("size_sf") && !$request->request->has("monthly_earning_usd") && !$request->request->has("sold")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("address") && !$request->filled("value_usd") && !$request->filled("image_urls") && !$request->filled("description") && !$request->filled("size_sf") && !$request->filled("monthly_earning_usd") && !$request->filled("sold")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                }
            } else if ($request->path() == "api/v1/user/update") {
                $request->validate([
                    "balance_usd" => ["bail", "prohibited"],
                    "is_admin" => ["bail", "prohibited"],
                    "email_verified" => ["bail", "prohibited"],
                    "identity_verification_status" => ["bail", "prohibited"],
                    "identity_verification_id" => ["bail", "prohibited"],
                    "phone_number" => ["bail", "prohibited"],
                    "full_name" => ["bail", "filled", "not_in:null"],
                    "dob" => ["bail", "filled", "date_format:d/m/Y"],
                    "email" => ["bail", "prohibited"],
                    "referral_code" => ["bail", "prohibited"],
                    "payment_customer_id" => ["bail", "prohibited"],
                    "payment_account_id" => ["bail", "prohibited"],
                    "nationality" => ["bail", "prohibited"],
                    "image_url" => ["bail", "prohibited"],
                    "gender" => ["bail", "prohibited"]
                ]);
                if (sizeof($request->all()) == 0) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("full_name") && !$request->request->has("dob")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("full_name") && !$request->filled("dob")) {
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
                                "first_name" => ucwords(strtolower($first_name)),
                                "last_name" => ucwords(strtolower($last_name))
                            ]);
                        }
                        $request->request->remove("full_name");
                    }
                }
            } else if ($request->path() == "api/v1/user/update_default_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/login/update_device_token") {
                $request->validate([
                    "device_token" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/investment/liquidate") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "amount_usd" => ["bail", "required", "numeric", "gte:0.50", "lte:999999.99"],
                    "percentage" => ["bail", "prohibited"]
                ]);
            } else if ($request->path() == "api/v1/notification/update") {
                $request->validate([
                    "notification_id" => ["bail", "prohibited_if:seen_all,true,1", "required_without:seen_all"],
                    "seen_all" => ["bail", "filled", "boolean"],
                    "seen" => ["bail", "filled", "boolean", "prohibited_if:seen_all,true,1"],
                    "tapped" => ["bail", "filled", "boolean", "prohibited_if:seen_all,true,1"],
                    "tappable" => ["bail", "prohibited"],
                    "redirection_page" => ["bail", "prohibited"],
                    "redirection_page_id" => ["bail", "prohibited"],
                    "sender_user_id" => ["bail", "prohibited"],
                    "receiver_user_id" => ["bail", "prohibited"],
                    "title" => ["bail", "prohibited"],
                    "body" => ["bail", "prohibited"]
                ]);

                if (sizeof($request->all()) < 1 || sizeof($request->all()) == 1 && $request->request->has("notification_id")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is nothing to update."
                    ], 400)->throwResponse();
                } else if (!$request->request->has("seen") && !$request->request->has("tapped") && !$request->request->has("seen_all")) {
                    return response()->json([
                        "status" => false,
                        "message" => "You provided an invalid key."
                    ], 400)->throwResponse();
                } else if (!$request->filled("seen") && !$request->filled("tapped") && !$request->filled("seen_all")) {
                    return response()->json([
                        "status" => false,
                        "message" => "There is no data to update."
                    ], 400)->throwResponse();
                }
            }
        } else if ($request->isMethod("get")) {
            if ($request->path() == "api/v1/investment/read_user_and_property_specific") {
                $request->validate([
                    "property_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/investment/read_property_specific") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/investment/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/investment/read_user_specific") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/property/read") {
                $request->validate([
                    "property_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/property/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/property/read_paid_dividend") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/property/read_property_value_history") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/payment/read" || $request->path() == "api/v1/payment/read_user_and_payment_specific") {
                $request->validate([
                    "payment_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/payment/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/payment/read_user_specific") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/payment/convert_currency") {
                $request->validate([
                    "amount" => ["bail", "required", "numeric", "gt:0"],
                    "from" => ["bail", "required", "alpha", "size:3"],
                    "to" => ["bail", "required", "alpha", "size:3"]
                ]);
            } else if ($request->path() == "api/v1/payment/read_all_bonus_and_fee") {
                $request->validate([
                    "type" => ["bail", "required", "in:bonus,fee"],
                    "amount_usd" => ["bail", "filled", "numeric", "gte:0.50", "lte:999999.99", "required_if:type,fee"]
                ]);
            } else if ($request->path() == "api/v1/notification/read") {
                $request->validate([
                    "notification_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/notification/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/notification/read_user_specific") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/user/read_earning") {
                $request->validate([
                    "property_id" => ["bail", "required"],
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/user/read_all_earning") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/user/read_specific") {
                $request->validate([
                    "user_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/user/read_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/user/read_all_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "type" => ["bail", "required", "in:card,bank_account"],
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "starting_after" => ["bail", "filled", "not_in:null"]
                ]);
            } else if ($request->path() == "api/v1/user/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            } else if ($request->path() == "api/v1/login/read_all") {
                $request->validate([
                    "limit" => ["bail", "filled", "numeric", "gte:1", "lte:100"],
                    "page" => ["bail", "filled", "numeric", "gte:1"]
                ]);
                if (!$request->request->has("limit")) {
                    $request->request->add([
                        "limit" => 10
                    ]);
                }
            }
        } else if ($request->isMethod("delete")) {
            if ($request->path() == "api/v1/property/delete") {
                $request->validate([
                    "property_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/payment/delete") {
                $request->validate([
                    "payment_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/notification/delete") {
                $request->validate([
                    "notification_id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/user/delete_payment_method") {
                $request->validate([
                    "action" => ["bail", "required", "in:deposit,withdrawal"],
                    "id" => ["bail", "required"]
                ]);
            } else if ($request->path() == "api/v1/login/delete") {
                $request->validate([
                    "everywhere" => ["bail", "filled", "boolean"]
                ]);
            }
        }
        return $next($request);
    }
}
