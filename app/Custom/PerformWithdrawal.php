<?php

namespace App\Custom;

use App\Models\FailedWithdrawal;
use App\Models\Payment;
use App\Models\User;

class PerformWithdrawal
{

    function __construct($user_id, $payment_id, $amount_usd, $type, $is_scheduler = false)
    {
        $request = ["user_id" => $user_id, "payment_id" => $payment_id, "amount_usd" => $amount_usd, "type" => $type];
        $payment_manager = new PaymentManager();
        $fee = $payment_manager->getPaymentProcessingFeeUsd($request["amount_usd"], $request["type"]);
        $user_balance = User::where("user_id", $request["user_id"])->value("balance_usd");
        if ($user_balance >= $request["amount_usd"] + $fee) {
            $list_all_account_card_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request["user_id"])->value("payment_account_id"), "data" => ["type" => "card", "limit" => 1]));
            $list_all_account_bank_account_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request["user_id"])->value("payment_account_id"), "data" => ["type" => "bank_account", "limit" => 1]));
            if (isset($list_all_account_card_response) && isset($list_all_account_card_response["data"]) || isset($list_all_account_bank_account_response) && isset($list_all_account_bank_account_response["data"])) {
                if (sizeof($list_all_account_card_response["data"]) > 0 || sizeof($list_all_account_bank_account_response["data"]) > 0) {
                    $retrieve_balance_response = $payment_manager->manage(array("type" => "retrieve_balance"));
                    if (isset($retrieve_balance_response) && isset($retrieve_balance_response["available"])) {
                        if ($is_scheduler || !$is_scheduler && !FailedWithdrawal::where("user_id", $user_id)->exists()) {
                            if ($retrieve_balance_response["available"][0]["amount"] >= $request["amount_usd"] + $fee) {
                                $withdraw_response = $payment_manager->manage(array("type" => "withdraw", "account_id" => User::where("user_id", $request["user_id"])->value("payment_account_id"), "data" => ["amount" => $request["amount_usd"] + $fee, "currency" => "usd"]));
                                if (isset($withdraw_response) && isset($withdraw_response["id"])) {
                                    $request["reference"] = $withdraw_response["id"];
                                    $user_balance = $user_balance - ($request["amount_usd"] + $fee);
                                } else {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "An error occurred while making payment, payment could not be made."
                                    ], 500);
                                }
                            } else {
                                if (!$is_scheduler) {
                                    $send = new EmailManager();
                                    $admin_user_ids = User::where("is_admin", true)->get()->pluck("user_id")->unique();
                                    $status = $send->sendInsufficientFundMessage(number_format($request["amount_usd"] + $fee, 2), $admin_user_ids);
                                    if (isset($status)) {
                                        FailedWithdrawal::create(["payment_id" => $payment_id, "user_id" => $user_id, "amount_usd" => $amount_usd]);
                                        //Avoid duplicate transactions.
                                        return response()->json([
                                            "status" => true,
                                            "message" => "Withdrawal request was received. Process will be completed shortly."
                                        ], 200);
                                    } else {
                                        return response()->json([
                                            "status" => false,
                                            "message" => "An error occurred while making payment, payment could not be made."
                                        ], 500);
                                    }
                                } else {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "An error occurred while making payment, payment could not be made."
                                    ], 500);
                                }
                            }
                        } else {
                            return response()->json([
                                "status" => false,
                                "message" => "There is still an ongoing withdrawal process. You can not make another withdrawal until the ongoing one is completed."
                            ], 409);
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
        Payment::Create($request);
        User::where("user_id", $request["user_id"])->update(["balance_usd" => $user_balance]);
        if ($is_scheduler) {
            FailedWithdrawal::where("payment_id", $payment_id)->delete();
        }
        return response()->json([
            "status" => true,
            "message" => "Payment made successfully."
        ], 201);
    }
}
