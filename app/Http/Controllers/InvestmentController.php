<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Payment;
use App\Models\Property;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function create(Request $request)
    {
        if (Property::find($request->request->get("property_id"))) {
            if (Payment::find($request->request->get("payment_id"))) {
                $property_value = Property::find($request->request->get("property_id"))->value("value_usd");
                $payment_amount = Payment::find($request->request->get("payment_id"))->value("amount_usd");
                $investment_percentage = ($payment_amount / $property_value) * 100;
                $request->request->add(["percentage" => $investment_percentage]);
                Investment::firstOrCreate(["payment_id" => $request->request->get("payment_id")], $request->all());
                return response()->json([
                    "status" => true,
                    "message" => "Investment created successfully."
                ], 201);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Payment not found."
                ], 404);
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

    public function readPaymentSpecific(Request $request)
    {
        if (sizeof(Investment::where("payment_id", $request->get("payment_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("payment_id", $request->get("payment_id"))->get()
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
        Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->delete();
        return response()->json([
            "status" => true,
            "message" => "Investment data deleted successfully.",
        ], 200);
    }
}
