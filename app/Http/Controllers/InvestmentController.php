<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function create(Request $request)
    {
        if (Property::find($request->request->get("property_id"))) {
            $payment_amount = $request->request->get("amount_paid_usd");
            $user_balance = User::find($request->request->get("user_id"))->value("balance_usd");
            if ($user_balance >= $payment_amount) {
                $property_value = Property::find($request->request->get("property_id"))->value("value_usd");
                $investment_percentage = ($payment_amount / $property_value) * 100;
                $current_property_percentage_available = Property::find($request->request->get("property_id"))->value("percentage_available");
                $current_property_value_available = $property_value * ($current_property_percentage_available / 100);
                if ($current_property_value_available >= $payment_amount) {
                    $new_property_percentage_available = $current_property_percentage_available - $investment_percentage;
                    Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);
                    if (sizeof(Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->get()) > 0) {
                        $current_amount_paid = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_paid_usd");
                        if ($current_amount_paid < 0.00) {
                            $current_amount_paid = 0.00;
                        }
                        $request->request->set("amount_paid_usd", $payment_amount + $current_amount_paid);
                        $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
                        $investment_percentage = $current_investment_percentage + $investment_percentage;
                    }
                    $request->request->add(["percentage" => $investment_percentage]);
                    Investment::updateOrCreate(["user_id" => $request->request->get("user_id"), "property_id" => $request->request->get("property_id")], $request->all());
                    $new_user_balance = $user_balance - $payment_amount;
                    User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);
                    return response()->json([
                        "status" => true,
                        "message" => "Investment created successfully."
                    ], 201);
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
        if (sizeof(Investment::where("property_id", $request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()) > 0) {
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
        if (sizeof(Investment::where("user_id", $request->request->get("user_id"))->get()) > 0) {
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
        if (sizeof(Investment::where("property_id", $request->get("property_id"))->get()) > 0) {
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
        if (sizeof(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()) > 0) {
            $liquidation_amount = $request->request->get("amount_usd");
            $current_investment_percentage = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("percentage");
            $current_amount_paid = Investment::where("user_id", $request->request->get("user_id"))->where("property_id", $request->request->get("property_id"))->value("amount_paid_usd");
            $property_value = Property::find($request->request->get("property_id"))->value("value_usd");
            $current_investment_value = $property_value * ($current_investment_percentage / 100);
            if ($current_investment_value >= $liquidation_amount) {
                $new_amount_paid = $current_amount_paid - $liquidation_amount;
                $request->request->add(["amount_paid_usd" => $new_amount_paid]);
                $request->request->remove("amount_usd");
                $liquidated_investment_percentage = ($liquidation_amount / $property_value) * 100;
                $new_investment_percentage = $current_investment_percentage - $liquidated_investment_percentage;
                $request->request->add(["percentage" => $new_investment_percentage]);

                $current_property_percentage_available = Property::find($request->request->get("property_id"))->value("percentage_available");
                $new_property_percentage_available = $current_property_percentage_available + $liquidated_investment_percentage;
                Property::where("property_id", $request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);

                Investment::where("property_id", $request->get("property_id"))->where("user_id", $request->request->get("user_id"))->update($request->all());

                $user_balance = User::find($request->request->get("user_id"))->value("balance_usd");
                $new_user_balance = $user_balance + $liquidation_amount;
                User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);

                if ($new_investment_percentage <= 0.00) {
                    Investment::where("property_id", $request->get("property_id"))->where("user_id", $request->request->get("user_id"))->delete();
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
