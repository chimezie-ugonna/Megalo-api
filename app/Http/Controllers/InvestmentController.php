<?php

namespace App\Http\Controllers;

use App\Custom\PaymentManager;
use App\Models\Investment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function create(Request $request)
    {
        $user_identity_verified = User::where("user_id", $request->request->get("user_id"))->value("identity_verified");
        if ($user_identity_verified) {
            if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
                $payment_amount = $request->request->get("amount_invested_usd");
                $payment_manager = new PaymentManager();
                $fee = $payment_manager->getPaymentProcessingFee($payment_amount) + $payment_manager->getInvestmentFee($payment_amount);
                $user_balance = User::where("user_id", $request->request->get("user_id"))->value("balance_usd");
                if ($user_balance >= $payment_amount) {
                    $property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                    $investment_percentage = (($payment_amount - $fee) / $property_value) * 100;
                    $current_property_percentage_available = Property::where("property_id", $request->request->get("property_id"))->value("percentage_available");
                    $current_property_value_available = $property_value * ($current_property_percentage_available / 100);
                    if ($current_property_value_available >= ($payment_amount - $fee)) {
                        $new_property_percentage_available = $current_property_percentage_available - $investment_percentage;
                        if (Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->exists()) {
                            $current_amount_invested = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_invested_usd");
                            if ($current_amount_invested < 0.00) {
                                $current_amount_invested = 0.00;
                            }
                            $request->request->set("amount_invested_usd", ($payment_amount - $fee) + $current_amount_invested);
                            $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
                            $investment_percentage = $current_investment_percentage + $investment_percentage;
                        }
                        if ($investment_percentage <= 10.00) {
                            $request->request->add(["percentage" => $investment_percentage]);
                            Investment::updateOrCreate(["user_id" => $request->request->get("user_id"), "property_id" => $request->request->get("property_id")], $request->all());
                            Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);
                            $new_user_balance = $user_balance - $payment_amount;
                            User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);
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
            $fee = 0.00;
            $initial_investment_period = strtotime(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->value("created_at"));
            $initial_investment_year = date("Y", $initial_investment_period);
            $initial_investment_month = date("m", $initial_investment_period);
            date_default_timezone_set("UTC");
            $current_year = date("Y");
            $current_month = date("m");
            $liquidation_amount = $request->request->get("amount_usd");
            $payment_manager = new PaymentManager();
            if ($current_year == $initial_investment_year) {
                $fee = $payment_manager->getEarlyLiquidationFee($liquidation_amount);
            } else if (($current_year - $initial_investment_year) <= 1) {
                if ($current_month < $initial_investment_month) {
                    $fee = $payment_manager->getEarlyLiquidationFee($liquidation_amount);
                }
            }
            $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
            $current_amount_invested = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_invested_usd");
            $property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
            $current_investment_value = $property_value * ($current_investment_percentage / 100);
            if ($current_investment_value >= $liquidation_amount) {
                $new_amount_invested = $current_amount_invested - $liquidation_amount;
                $request->request->add(["amount_invested_usd" => $new_amount_invested]);
                $request->request->remove("amount_usd");
                $liquidated_investment_percentage = ($liquidation_amount / $property_value) * 100;
                $new_investment_percentage = $current_investment_percentage - $liquidated_investment_percentage;
                $request->request->add(["percentage" => $new_investment_percentage]);

                $current_property_percentage_available = Property::where("property_id", $request->request->get("property_id"))->value("percentage_available");
                $new_property_percentage_available = $current_property_percentage_available + $liquidated_investment_percentage;

                Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->update($request->all());
                Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);

                $user_balance = User::where("user_id", $request->request->get("user_id"))->value("balance_usd");
                $new_user_balance = $user_balance + ($liquidation_amount - $fee);
                User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);

                if ($new_investment_percentage <= 0.00) {
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
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }
}
