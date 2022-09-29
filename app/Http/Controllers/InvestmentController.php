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
            $payment_amount = number_format($request->request->get("amount_usd"));
            $user_balance = User::find($request->request->get("user_id"))->value("balance_usd");
            if ($user_balance >= $payment_amount) {
                $property_value = Property::find($request->request->get("property_id"))->value("value_usd");
                $investment_percentage = ($payment_amount / $property_value) * 100;
                $current_property_percentage_available = Property::find($request->request->get("property_id"))->value("percentage_available");
                $new_property_percentage_available = $current_property_percentage_available - $investment_percentage;
                Property::find($request->request->get("property_id"))->update(["percentage_available" => $new_property_percentage_available]);
                $request->request->add(["percentage" => $investment_percentage]);
                Investment::Create($request->all());
                $new_user_balance = $user_balance - $payment_amount;
                User::find($request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);
                return response()->json([
                    "status" => true,
                    "message" => "Investment created successfully."
                ], 201);
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

    public function delete(Request $request)
    {
        if (sizeof(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()) > 0) {
            Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->delete();
            return response()->json([
                "status" => true,
                "message" => "Investment data deleted successfully.",
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }
}
