<?php

namespace App\Http\Controllers;

use App\Custom\CurrencyConverter;
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

    public function readAllCurrency()
    {
        $currencies = array(
            "ALL" => "Albania Lek",
            "AFN" => "Afghanistan Afghani",
            "ARS" => "Argentina Peso",
            "AWG" => "Aruba Guilder",
            "AUD" => "Australia Dollar",
            "AZN" => "Azerbaijan New Manat",
            "BSD" => "Bahamas Dollar",
            "BBD" => "Barbados Dollar",
            "BDT" => "Bangladeshi taka",
            "BYR" => "Belarus Ruble",
            "BZD" => "Belize Dollar",
            "BMD" => "Bermuda Dollar",
            "BOB" => "Bolivia Boliviano",
            "BAM" => "Bosnia and Herzegovina Convertible Marka",
            "BWP" => "Botswana Pula",
            "BGN" => "Bulgaria Lev",
            "BRL" => "Brazil Real",
            "BND" => "Brunei Darussalam Dollar",
            "KHR" => "Cambodia Riel",
            "CAD" => "Canada Dollar",
            "KYD" => "Cayman Islands Dollar",
            "CLP" => "Chile Peso",
            "CNY" => "China Yuan Renminbi",
            "COP" => "Colombia Peso",
            "CRC" => "Costa Rica Colon",
            "HRK" => "Croatia Kuna",
            "CUP" => "Cuba Peso",
            "CZK" => "Czech Republic Koruna",
            "DKK" => "Denmark Krone",
            "DOP" => "Dominican Republic Peso",
            "XCD" => "East Caribbean Dollar",
            "EGP" => "Egypt Pound",
            "SVC" => "El Salvador Colon",
            "EEK" => "Estonia Kroon",
            "EUR" => "Euro Member Countries",
            "FKP" => "Falkland Islands (Malvinas) Pound",
            "FJD" => "Fiji Dollar",
            "GHC" => "Ghana Cedis",
            "GIP" => "Gibraltar Pound",
            "GTQ" => "Guatemala Quetzal",
            "GGP" => "Guernsey Pound",
            "GYD" => "Guyana Dollar",
            "HNL" => "Honduras Lempira",
            "HKD" => "Hong Kong Dollar",
            "HUF" => "Hungary Forint",
            "ISK" => "Iceland Krona",
            "INR" => "India Rupee",
            "IDR" => "Indonesia Rupiah",
            "IRR" => "Iran Rial",
            "IMP" => "Isle of Man Pound",
            "ILS" => "Israel Shekel",
            "JMD" => "Jamaica Dollar",
            "JPY" => "Japan Yen",
            "JEP" => "Jersey Pound",
            "KZT" => "Kazakhstan Tenge",
            "KES" => "Kenyan Shilling",
            "KPW" => "Korea (North) Won",
            "KRW" => "Korea (South) Won",
            "KGS" => "Kyrgyzstan Som",
            "LAK" => "Laos Kip",
            "LVL" => "Latvia Lat",
            "LBP" => "Lebanon Pound",
            "LRD" => "Liberia Dollar",
            "LTL" => "Lithuania Litas",
            "MKD" => "Macedonia Denar",
            "MYR" => "Malaysia Ringgit",
            "MUR" => "Mauritius Rupee",
            "MXN" => "Mexico Peso",
            "MNT" => "Mongolia Tughrik",
            "MZN" => "Mozambique Metical",
            "NAD" => "Namibia Dollar",
            "NPR" => "Nepal Rupee",
            "ANG" => "Netherlands Antilles Guilder",
            "NZD" => "New Zealand Dollar",
            "NIO" => "Nicaragua Cordoba",
            "NGN" => "Nigeria Naira",
            "NOK" => "Norway Krone",
            "OMR" => "Oman Rial",
            "PKR" => "Pakistan Rupee",
            "PAB" => "Panama Balboa",
            "PYG" => "Paraguay Guarani",
            "PEN" => "Peru Nuevo Sol",
            "PHP" => "Philippines Peso",
            "PLN" => "Poland Zloty",
            "QAR" => "Qatar Riyal",
            "RON" => "Romania New Leu",
            "RUB" => "Russia Ruble",
            "SHP" => "Saint Helena Pound",
            "SAR" => "Saudi Arabia Riyal",
            "RSD" => "Serbia Dinar",
            "SCR" => "Seychelles Rupee",
            "SGD" => "Singapore Dollar",
            "SBD" => "Solomon Islands Dollar",
            "SOS" => "Somalia Shilling",
            "ZAR" => "South Africa Rand",
            "LKR" => "Sri Lanka Rupee",
            "SEK" => "Sweden Krona",
            "CHF" => "Switzerland Franc",
            "SRD" => "Suriname Dollar",
            "SYP" => "Syria Pound",
            "TWD" => "Taiwan New Dollar",
            "THB" => "Thailand Baht",
            "TTD" => "Trinidad and Tobago Dollar",
            "TRY" => "Turkey Lira",
            "TRL" => "Turkey Lira",
            "TVD" => "Tuvalu Dollar",
            "UAH" => "Ukraine Hryvna",
            "GBP" => "United Kingdom Pound",
            "USD" => "United States Dollar",
            "UYU" => "Uruguay Peso",
            "UZS" => "Uzbekistan Som",
            "VEF" => "Venezuela Bolivar",
            "VND" => "Viet Nam Dong",
            "YER" => "Yemen Rial",
            "ZWD" => "Zimbabwe Dollar"
        );
        return response()->json([
            "status" => true,
            "message" => "All currency data retrieved successfully.",
            "data" => ksort($currencies)
        ], 200);
    }

    public function convertCurrency(Request $request)
    {
        $currency_converter = new CurrencyConverter();
        $response = $currency_converter->convert($request->get("amount"), $request->get("from"), $request->get("to"));
        if (isset($response)) {
            return response()->json([
                "status" => true,
                "message" => "Currency converted successfully.",
                "data" => json_decode($response)
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while converting currency, currency conversion failed."
            ], 500);
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
