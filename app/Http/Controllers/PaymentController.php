<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $payment_amount = $request->request->get("amount_usd");
        $user_balance = User::find($request->request->get("user_id"))->value("balance_usd");
        $new_user_balance = $user_balance;
        if ($request->request->get("type") == "deposit") {
            //charge user's current payment method with provided amount successfully using stripe api. If successful, move to next line.
            $request->request->add(["reference" => uniqid(rand(), true)]);/* Add correct stripe reference after payment is made. */
            $new_user_balance = $user_balance + $payment_amount;
        } else if ($request->request->get("type") == "withdrawal") {
            if ($user_balance >= $payment_amount) {
                //check if there is enough money in company balance to send to user. If there is not enough money, return false and a message. If there is enough money, send it to user's current payment method successfully using stripe api. 
                $request->request->add(["reference" => uniqid(rand(), true)]);/* Add correct stripe reference after payment is made. */
                $new_user_balance = $user_balance - $payment_amount;
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User does not have sufficient fund in balance for this withdrawal."
                ], 402);
            }
        }
        Payment::Create($request->all());
        User::find($request->request->get("user_id"))->update(["balance_usd" => $new_user_balance]);
        return response()->json([
            "status" => true,
            "message" => "Payment made successfully."
        ], 201);
    }

    public function read(Request $request)
    {
        if (Payment::find($request->get("payment_id"))) {
            return response()->json([
                "status" => true,
                "message" => "Payment data retrieved successfully.",
                "data" => Payment::where("payment_id", $request->get("payment_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Payment data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        if (sizeof(Payment::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All payment data retrieved successfully.",
                "data" => Payment::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No payment data found."
            ], 404);
        }
    }

    public function readUserSpecific(Request $request)
    {
        if (sizeof(Payment::where("user_id", $request->request->get("user_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "Payment data retrieved successfully.",
                "data" => Payment::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Payment data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        if (Payment::find($request->request->get("payment_id"))) {
            Payment::destroy($request->request->get("payment_id"));
            return response()->json([
                "status" => true,
                "message" => "Payment data deleted successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Payment data not found."
            ], 404);
        }
    }
}
