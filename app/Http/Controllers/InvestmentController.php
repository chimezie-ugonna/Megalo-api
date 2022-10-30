<?php

namespace App\Http\Controllers;

use App\Custom\NotificationManager;
use App\Custom\PaymentManager;
use App\Models\Investment;
use App\Models\Property;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function create(Request $request)
    {
        $user_identity_verified = User::where("user_id", $request->request->get("user_id"))->value("identity_verified");
        if ($user_identity_verified) {
            if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
                if ($request->request->get("amount_invested_usd") >= 0.50 && $request->request->get("amount_invested_usd") <= 999999.99) {
                    $amount_invested_usd = $request->request->get("amount_invested_usd");
                    $payment_manager = new PaymentManager();
                    $fee = $payment_manager->getPaymentProcessingFee($request->request->get("amount_invested_usd")) + $payment_manager->getInvestmentFee($request->request->get("amount_invested_usd"));
                    $user_balance = User::where("user_id", $request->request->get("user_id"))->value("balance_usd");
                    if ($user_balance >= $request->request->get("amount_invested_usd")) {
                        $request->request->set("amount_invested_usd", round($request->request->get("amount_invested_usd") - $fee, 2));
                        $property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                        $investment_percentage = ($request->request->get("amount_invested_usd") / $property_value) * 100;
                        $current_property_percentage_available = Property::where("property_id", $request->request->get("property_id"))->value("percentage_available");
                        $current_property_value_available = $property_value * ($current_property_percentage_available / 100);
                        if ($current_property_value_available >= $request->request->get("amount_invested_usd")) {
                            $new_property_percentage_available = $current_property_percentage_available - $investment_percentage;
                            if (Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->exists()) {
                                $current_amount_invested = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_invested_usd");
                                $request->request->set("amount_invested_usd", $request->request->get("amount_invested_usd") + $current_amount_invested);
                                $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
                                $investment_percentage = $current_investment_percentage + $investment_percentage;
                            }
                            if ($investment_percentage <= 10) {
                                $request->request->add(["percentage" => $investment_percentage]);
                                Investment::updateOrCreate(["user_id" => $request->request->get("user_id"), "property_id" => $request->request->get("property_id")], $request->all());
                                Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);
                                $new_user_balance = $user_balance - $amount_invested_usd;
                                User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);

                                if (Referral::where("referree_phone_number", User::where("user_id", $request->request->get("user_id"))->value("phone_number"))->where("rewarded", false)->exists() || Referral::where("referree_user_id", $request->request->get("user_id"))->where("rewarded", false)->exists()) {
                                    $referree_investments = Investment::where("user_id", $request->request->get("user_id"))->get()->pluck("amount_invested_usd");
                                    if (count($referree_investments) > 0) {
                                        $total_amount_invested_usd = 0;
                                        foreach ($referree_investments as $amount_invested_usd) {
                                            $total_amount_invested_usd += $amount_invested_usd;
                                        }
                                        if ($total_amount_invested_usd >= 100) {
                                            $referral_payment_usd = $payment_manager->getReferralBonus();
                                            if (Referral::where("referree_phone_number", User::where("user_id", $request->request->get("user_id"))->value("phone_number"))->where("rewarded", false)->exists()) {
                                                $referrer_user_id = Referral::where("referree_phone_number", User::where("user_id", $request->request->get("user_id"))->value("phone_number"))->where("rewarded", false)->value("referrer_user_id");
                                                $referree_user_id = Referral::where("referree_phone_number", User::where("user_id", $request->request->get("user_id"))->value("phone_number"))->where("rewarded", false)->value("referree_user_id");
                                            } else {
                                                $referrer_user_id = Referral::where("referree_user_id", $request->request->get("user_id"))->where("rewarded", false)->value("referrer_user_id");
                                                $referree_user_id = Referral::where("referree_user_id", $request->request->get("user_id"))->where("rewarded", false)->value("referree_user_id");
                                            }
                                            if (User::where("user_id", $referrer_user_id)->exists()) {
                                                $referrer_balance = User::where("user_id", $referrer_user_id)->value("balance_usd");
                                                $new_referrer_balance = $referrer_balance + $referral_payment_usd;
                                                User::where("user_id", $referrer_user_id)->update(["balance_usd" => $new_referrer_balance]);
                                                $notification_manager = new NotificationManager();
                                                $notification_manager->sendNotification(array(
                                                    "receiver_user_id" => $referrer_user_id,
                                                    "title" => "Referral bonus received!!!",
                                                    "body" => "You have just received $" . $referral_payment_usd . " in your balance because someone you referred with your referral code has invested $100 or more on Megalo. Keep referring people to earn more!",
                                                    "tappable" => true,
                                                    "redirection_page" => "balance",
                                                    "redirection_page_id" => ""
                                                ), array(), "user_specific");
                                            }

                                            if (User::where("user_id", $referree_user_id)->exists()) {
                                                $referree_balance = User::where("user_id", $referree_user_id)->value("balance_usd");
                                                $new_referree_balance = $referree_balance + $referral_payment_usd;
                                                User::where("user_id", $referree_user_id)->update(["balance_usd" => $new_referree_balance]);
                                                $notification_manager = new NotificationManager();
                                                $notification_manager->sendNotification(array(
                                                    "receiver_user_id" => $referree_user_id,
                                                    "title" => "Referral bonus received!!!",
                                                    "body" => "You have just received $" . $referral_payment_usd . " in your balance because you joined Megalo with someone's referral code and have invested $100 or more on Megalo. You can earn more if you refer someone too.",
                                                    "tappable" => true,
                                                    "redirection_page" => "balance",
                                                    "redirection_page_id" => ""
                                                ), array(), "user_specific");
                                            }

                                            Referral::where("referrer_user_id", $referrer_user_id)->where("referree_user_id", $referree_user_id)->update(["rewarded" => true]);
                                        }
                                    }
                                }
                                return response()->json([
                                    "status" => true,
                                    "message" => "Investment created successfully."
                                ], 201);
                            } else {
                                return response()->json([
                                    "status" => false,
                                    "message" => "Each user can purchase no more than 10% of a property."
                                ], 402);
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "Investment amount exceeds the available amount on property."
                            ], 402);
                        }
                    } else {
                        return response()->json([
                            "status" => false,
                            "message" => "User does not have sufficient fund in balance for this investment."
                        ], 402);
                    }
                } else if ($request->request->get("amount_invested_usd") > 999999.99) {
                    return response()->json([
                        "status" => false,
                        "message" => "You can only invest a maximum of $999,999.99 at once. You can invest more subsequently."
                    ], 402);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "You can only invest a minimum of $0.50."
                    ], 402);
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Property not found."
                ], 404);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "User identity has to be verified before any investment can be made."
            ], 401);
        }
    }

    public function readAll()
    {
        if (sizeof(Investment::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All investment data retrieved successfully.",
                "data" => Investment::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No investment data found."
            ], 404);
        }
    }

    public function readUserAndPropertySpecific(Request $request)
    {
        if (Investment::where("property_id", $request->get("property_id"))->where("user_id", $request->request->get("user_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("property_id", $request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function readUserSpecific(Request $request)
    {
        if (Investment::where("user_id", $request->request->get("user_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function readPropertySpecific(Request $request)
    {
        if (Investment::where("property_id", $request->get("property_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("property_id", $request->get("property_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function liquidate(Request $request)
    {
        if (Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->exists()) {
            if ($request->request->get("amount_usd") >= 0.50 && $request->request->get("amount_usd") <= 999999.99) {
                $fee = 0;
                $initial_investment_period = strtotime(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->value("created_at"));
                $initial_investment_year = date("Y", $initial_investment_period);
                $initial_investment_month = date("m", $initial_investment_period);
                date_default_timezone_set("UTC");
                $current_year = date("Y");
                $current_month = date("m");
                $payment_manager = new PaymentManager();
                if ($current_year == $initial_investment_year) {
                    $fee = $payment_manager->getEarlyLiquidationFee($request->request->get("amount_usd"));
                } else if (($current_year - $initial_investment_year) <= 1) {
                    if ($current_month < $initial_investment_month) {
                        $fee = $payment_manager->getEarlyLiquidationFee($request->request->get("amount_usd"));
                    }
                }
                $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
                $current_amount_invested = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_invested_usd");
                $property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                $current_investment_value = ($current_investment_percentage / 100) * $property_value;
                if ($current_investment_value >= $request->request->get("amount_usd")) {
                    $new_amount_invested = round($current_amount_invested - $request->request->get("amount_usd"), 2);
                    if ($new_amount_invested < 0) {
                        $new_amount_invested = 0;
                    }
                    $request->request->add(["amount_invested_usd" => $new_amount_invested]);
                    $liquidated_investment_percentage = ($request->request->get("amount_usd") / $property_value) * 100;
                    $new_investment_percentage = $current_investment_percentage - $liquidated_investment_percentage;
                    $request->request->add(["percentage" => $new_investment_percentage]);

                    $current_property_percentage_available = Property::where("property_id", $request->request->get("property_id"))->value("percentage_available");
                    $new_property_percentage_available = $current_property_percentage_available + $liquidated_investment_percentage;

                    Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->update($request->except(["amount_usd"]));
                    Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);

                    $user_balance = User::where("user_id", $request->request->get("user_id"))->value("balance_usd");
                    $new_user_balance = $user_balance + ($request->request->get("amount_usd") - $fee);
                    User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);

                    if ($new_investment_percentage <= 0) {
                        Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->delete();
                    }

                    return response()->json([
                        "status" => true,
                        "message" => "Investment liquidated successfully."
                    ], 200);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "Liquidation amount exceeds user's investment value on property."
                    ], 402);
                }
            } else if ($request->request->get("amount_usd") > 999999.99) {
                return response()->json([
                    "status" => false,
                    "message" => "You can only liquidate a maximum of $999,999.99 at once. You can liquidate more subsequently."
                ], 402);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "You can only liquidate a minimum of $0.50."
                ], 402);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }
}
