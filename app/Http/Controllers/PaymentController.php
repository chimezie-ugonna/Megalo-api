<?php

namespace App\Http\Controllers;

use App\Custom\PaymentManager;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $user_identity_verified = User::where("user_id", $request->request->get("user_id"))->value("identity_verified");
        if ($user_identity_verified) {
            if ($request->request->get("amount_usd") >= 0.50) {
                $user_balance = User::where("user_id", $request->request->get("user_id"))->value("balance_usd");
                $payment_manager = new PaymentManager();
                if ($request->request->get("type") == "deposit") {
                    $list_all_customer_card_response = $payment_manager->manage(array("type" => "list_all_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["type" => "card", "limit" => 1]));
                    $list_all_customer_bank_account_response = $payment_manager->manage(array("type" => "list_all_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["type" => "bank_account", "limit" => 1]));
                    if (isset($list_all_customer_card_response) && isset($list_all_customer_card_response["data"]) || isset($list_all_customer_bank_account_response) && isset($list_all_customer_bank_account_response["data"])) {
                        if (sizeof($list_all_customer_card_response["data"]) > 0 || sizeof($list_all_customer_bank_account_response["data"]) > 0) {
                            $deposit_response = $payment_manager->manage(array("type" => "deposit", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["amount" => $request->request->get("amount_usd"), "currency" => "usd"]));
                            if (isset($deposit_response) && isset($deposit_response["id"])) {
                                $request->request->add(["reference" => $deposit_response["id"]]);
                                $user_balance = $user_balance + $request->request->get("amount_usd");
                            } else {
                                return response()->json([
                                    "status" => false,
                                    "message" => "An error occurred while making payment, payment could not be made."
                                ], 500);
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "No payment method found."
                            ], 404);
                        }
                    } else {
                        return response()->json([
                            "status" => false,
                            "message" => "An error occurred while making payment, payment could not be made."
                        ], 500);
                    }
                } else if ($request->request->get("type") == "withdrawal") {
                    if ($user_balance >= $request->request->get("amount_usd")) {
                        $list_all_account_card_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["type" => "card", "limit" => 1]));
                        $list_all_account_bank_account_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["type" => "bank_account", "limit" => 1]));
                        if (isset($list_all_account_card_response) && isset($list_all_account_card_response["data"]) || isset($list_all_account_bank_account_response) && isset($list_all_account_bank_account_response["data"])) {
                            if (sizeof($list_all_account_card_response["data"]) > 0 || sizeof($list_all_account_bank_account_response["data"]) > 0) {
                                $retrieve_balance_response = $payment_manager->manage(array("type" => "retrieve_balance"));
                                if (isset($retrieve_balance_response) && isset($retrieve_balance_response["available"])) {
                                    if ($retrieve_balance_response["available"][0]["amount"] >= $request->request->get("amount_usd")) {
                                        $withdraw_response = $payment_manager->manage(array("type" => "withdraw", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["amount" => $request->request->get("amount_usd"), "currency" => "usd"]));
                                        if (isset($withdraw_response) && isset($withdraw_response["id"])) {
                                            $request->request->add(["reference" => $withdraw_response["id"]]);
                                            $user_balance = $user_balance - $request->request->get("amount_usd");
                                        } else {
                                            return response()->json([
                                                "status" => false,
                                                "message" => "An error occurred while making payment, payment could not be made."
                                            ], 500);
                                        }
                                    } else {
                                        return response()->json([
                                            "status" => false,
                                            "message" => "No sufficient fund in Company's balance for this withdrawal."
                                        ], 402);
                                    }
                                } else {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "An error occurred while making payment, payment could not be made."
                                    ], 500);
                                }
                            } else {
                                return response()->json([
                                    "status" => false,
                                    "message" => "No payment method found."
                                ], 404);
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "An error occurred while making payment, payment could not be made."
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            "status" => false,
                            "message" => "User does not have sufficient fund in balance for this withdrawal."
                        ], 402);
                    }
                }
                Payment::Create($request->all());
                User::where("user_id", $request->request->get("user_id"))->update(["balance_usd" => $user_balance]);
                return response()->json([
                    "status" => true,
                    "message" => "Payment made successfully."
                ], 201);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "You can only initiate payments of $0.50 or higher."
                ], 402);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "User identity has to be verified before any payment can be made."
            ], 401);
        }
    }

    public function read(Request $request)
    {
        if (Payment::where("payment_id", $request->get("payment_id"))->exists()) {
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
        if (Payment::where("user_id", $request->request->get("user_id"))->exists()) {
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
        if (Payment::where("payment_id", $request->request->get("payment_id"))->exists()) {
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
