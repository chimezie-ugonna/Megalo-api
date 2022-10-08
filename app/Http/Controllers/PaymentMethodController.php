<?php

namespace App\Http\Controllers;

use App\Custom\PaymentManager;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function create(Request $request)
    {
        $payment_manager = new PaymentManager();
    }

    public function read(Request $request)
    {
        if (PaymentMethod::find($request->get("payment_method_id"))) {
            return response()->json([
                "status" => true,
                "message" => "Payment method data retrieved successfully.",
                "data" => PaymentMethod::where("payment_method_id", $request->get("payment_method_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Payment method data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        if (sizeof(PaymentMethod::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All payment method data retrieved successfully.",
                "data" => PaymentMethod::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No payment method data found."
            ], 404);
        }
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
        if (PaymentMethod::find($request->request->get("payment_method_id"))) {
            PaymentMethod::destroy($request->request->get("payment_method_id"));
            return response()->json([
                "status" => true,
                "message" => "Payment method data deleted successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Payment method data not found."
            ], 404);
        }
    }
}
