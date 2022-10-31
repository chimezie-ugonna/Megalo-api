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
            array(
                'code' => 'ALL',
                'countryname' => 'Albania',
                'name' => 'Albanian lek',
                'symbol' => 'L'
            ),

            array(
                'code' => 'AFN',
                'countryname' => 'Afghanistan',
                'name' => 'Afghanistan Afghani',
                'symbol' => '&#1547;'
            ),

            array(
                'code' => 'ARS',
                'countryname' => 'Argentina',
                'name' => 'Argentine Peso',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'AWG',
                'countryname' => 'Aruba',
                'name' => 'Aruban florin',
                'symbol' => '&#402;'
            ),

            array(
                'code' => 'AUD',
                'countryname' => 'Australia',
                'name' => 'Australian Dollar',
                'symbol' => '&#65;&#36;'
            ),

            array(
                'code' => 'AZN',
                'countryname' => 'Azerbaijan',
                'name' => 'Azerbaijani Manat',
                'symbol' => '&#8380;'
            ),

            array(
                'code' => 'BSD',
                'countryname' => 'The Bahamas',
                'name' => 'Bahamas Dollar',
                'symbol' => '&#66;&#36;'
            ),

            array(
                'code' => 'BBD',
                'countryname' => 'Barbados',
                'name' => 'Barbados Dollar',
                'symbol' => '&#66;&#100;&#115;&#36;'
            ),

            array(
                'code' => 'BDT',
                'countryname' => 'People\'s Republic of Bangladesh',
                'name' => 'Bangladeshi taka',
                'symbol' => '&#2547;'
            ),

            array(
                'code' => 'BYN',
                'countryname' => 'Belarus',
                'name' => 'Belarus Ruble',
                'symbol' => '&#66;&#114;'
            ),

            array(
                'code' => 'BZD',
                'countryname' => 'Belize',
                'name' => 'Belize Dollar',
                'symbol' => '&#66;&#90;&#36;'
            ),

            array(
                'code' => 'BMD',
                'countryname' => 'British Overseas Territory of Bermuda',
                'name' => 'Bermudian Dollar',
                'symbol' => '&#66;&#68;&#36;'
            ),

            array(
                'code' => 'BOP',
                'countryname' => 'Bolivia',
                'name' => 'Boliviano',
                'symbol' => '&#66;&#115;'
            ),

            array(
                'code' => 'BAM',
                'countryname' => 'Bosnia and Herzegovina',
                'name' => 'Bosnia-Herzegovina Convertible Marka',
                'symbol' => '&#75;&#77;'
            ),

            array(
                'code' => 'BWP',
                'countryname' => 'Botswana',
                'name' => 'Botswana pula',
                'symbol' => '&#80;'
            ),

            array(
                'code' => 'BGN',
                'countryname' => 'Bulgaria',
                'name' => 'Bulgarian lev',
                'symbol' => '&#1083;&#1074;'
            ),

            array(
                'code' => 'BRL',
                'countryname' => 'Brazil',
                'name' => 'Brazilian real',
                'symbol' => '&#82;&#36;'
            ),

            array(
                'code' => 'BND',
                'countryname' => 'Sultanate of Brunei',
                'name' => 'Brunei dollar',
                'symbol' => '&#66;&#36;'
            ),

            array(
                'code' => 'KHR',
                'countryname' => 'Cambodia',
                'name' => 'Cambodian riel',
                'symbol' => '&#6107;'
            ),

            array(
                'code' => 'CAD',
                'countryname' => 'Canada',
                'name' => 'Canadian dollar',
                'symbol' => '&#67;&#36;'
            ),

            array(
                'code' => 'KYD',
                'countryname' => 'Cayman Islands',
                'name' => 'Cayman Islands dollar',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'CLP',
                'countryname' => 'Chile',
                'name' => 'Chilean peso',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'CNY',
                'countryname' => 'China',
                'name' => 'Chinese Yuan Renminbi',
                'symbol' => '&#165;'
            ),

            array(
                'code' => 'COP',
                'countryname' => 'Colombia',
                'name' => 'Colombian peso',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'CRC',
                'countryname' => 'Costa Rica',
                'name' => 'Costa Rican colón',
                'symbol' => '&#8353;'
            ),

            array(
                'code' => 'HRK',
                'countryname' => 'Croatia',
                'name' => 'Croatian kuna',
                'symbol' => '&#107;&#110;'
            ),

            array(
                'code' => 'CUP',
                'countryname' => 'Cuba',
                'name' => 'Cuban peso',
                'symbol' => '&#8369;'
            ),

            array(
                'code' => 'CZK',
                'countryname' => 'Czech Republic',
                'name' => 'Czech koruna',
                'symbol' => '&#75;&#269;'
            ),

            array(
                'code' => 'DKK',
                'countryname' => 'Denmark, Greenland, and the Faroe Islands',
                'name' => 'Danish krone',
                'symbol' => '&#107;&#114;'
            ),

            array(
                'code' => 'DOP',
                'countryname' => 'Dominican Republic',
                'name' => 'Dominican peso',
                'symbol' => '&#82;&#68;&#36;'
            ),

            array(
                'code' => 'XCD',
                'countryname' => 'Antigua and Barbuda, Commonwealth of Dominica, Grenada, Montserrat, St. Kitts and Nevis, Saint Lucia and St. Vincent and the Grenadines',
                'name' => 'Eastern Caribbean dollar',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'EGP',
                'countryname' => 'Egypt',
                'name' => 'Egyptian pound',
                'symbol' => '&#163;'
            ),

            array(
                'code' => 'SVC',
                'countryname' => 'El Salvador',
                'name' => 'Salvadoran colón',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'EEK',
                'countryname' => 'Estonia',
                'name' => 'Estonian kroon',
                'symbol' => '&#75;&#114;'
            ),

            array(
                'code' => 'EUR',
                'countryname' => 'European Union, Italy, Belgium, Bulgaria, Croatia, Cyprus, Czechia, Denmark, Estonia, Finland, France, Germany, 
                                Greece, Hungary, Ireland, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Poland, 
                                Portugal, Romania, Slovakia, Slovenia, Spain, Sweden',
                'name' => 'Euro',
                'symbol' => '&#8364;'
            ),

            array(
                'code' => 'FKP',
                'countryname' => 'Falkland Islands',
                'name' => 'Falkland Islands (Malvinas) Pound',
                'symbol' => '&#70;&#75;&#163;'
            ),

            array(
                'code' => 'FJD',
                'countryname' => 'Fiji',
                'name' => 'Fijian dollar',
                'symbol' => '&#70;&#74;&#36;'
            ),

            array(
                'code' => 'GHC',
                'countryname' => 'Ghana',
                'name' => 'Ghanaian cedi',
                'symbol' => '&#71;&#72;&#162;'
            ),

            array(
                'code' => 'GIP',
                'countryname' => 'Gibraltar',
                'name' => 'Gibraltar pound',
                'symbol' => '&#163;'
            ),

            array(
                'code' => 'GTQ',
                'countryname' => 'Guatemala',
                'name' => 'Guatemalan quetzal',
                'symbol' => '&#81;'
            ),

            array(
                'code' => 'GGP',
                'countryname' => 'Guernsey',
                'name' => 'Guernsey pound',
                'symbol' => '&#81;'
            ),

            array(
                'code' => 'GYD',
                'countryname' => 'Guyana',
                'name' => 'Guyanese dollar',
                'symbol' => '&#71;&#89;&#36;'
            ),

            array(
                'code' => 'HNL',
                'countryname' => 'Honduras',
                'name' => 'Honduran lempira',
                'symbol' => '&#76;'
            ),

            array(
                'code' => 'HKD',
                'countryname' => 'Hong Kong',
                'name' => 'Hong Kong dollar',
                'symbol' => '&#72;&#75;&#36;'
            ),

            array(
                'code' => 'HUF',
                'countryname' => 'Hungary',
                'name' => 'Hungarian forint',
                'symbol' => '&#70;&#116;'
            ),

            array(
                'code' => 'ISK',
                'countryname' => 'Iceland',
                'name' => 'Icelandic króna',
                'symbol' => '&#237;&#107;&#114;'
            ),

            array(
                'code' => 'INR',
                'countryname' => 'India',
                'name' => 'Indian rupee',
                'symbol' => '&#8377;'
            ),

            array(
                'code' => 'IDR',
                'countryname' => 'Indonesia',
                'name' => 'Indonesian rupiah',
                'symbol' => '&#82;&#112;'
            ),

            array(
                'code' => 'IRR',
                'countryname' => 'Iran',
                'name' => 'Iranian rial',
                'symbol' => '&#65020;'
            ),

            array(
                'code' => 'IMP',
                'countryname' => 'Isle of Man',
                'name' => 'Manx pound',
                'symbol' => '&#163;'
            ),

            array(
                'code' => 'ILS',
                'countryname' => 'Israel, Palestinian territories of the West Bank and the Gaza Strip',
                'name' => 'Israeli Shekel',
                'symbol' => '&#8362;'
            ),

            array(
                'code' => 'JMD',
                'countryname' => 'Jamaica',
                'name' => 'Jamaican dollar',
                'symbol' => '&#74;&#36;'
            ),

            array(
                'code' => 'JPY',
                'countryname' => 'Japan',
                'name' => 'Japanese yen',
                'symbol' => '&#165;'
            ),

            array(
                'code' => 'JEP',
                'countryname' => 'Jersey',
                'name' => 'Jersey pound',
                'symbol' => '&#163;'
            ),

            array(
                'code' => 'KZT',
                'countryname' => 'Kazakhstan',
                'name' => 'Kazakhstani tenge',
                'symbol' => '&#8376;'
            ),

            array(
                'code' => 'KPW',
                'countryname' => 'North Korea',
                'name' => 'North Korean won',
                'symbol' => '&#8361;'
            ),

            array(
                'code' => 'KPW',
                'countryname' => 'South Korea',
                'name' => 'South Korean won',
                'symbol' => '&#8361;'
            ),

            array(
                'code' => 'KGS',
                'countryname' => 'Kyrgyz Republic',
                'name' => 'Kyrgyzstani som',
                'symbol' => '&#1083;&#1074;'
            ),

            array(
                'code' => 'LAK',
                'countryname' => 'Laos',
                'name' => 'Lao kip',
                'symbol' => '&#8365;'
            ),

            array(
                'code' => 'LAK',
                'countryname' => 'Laos',
                'name' => 'Latvian lats',
                'symbol' => '&#8364;'
            ),

            array(
                'code' => 'LVL',
                'countryname' => 'Laos',
                'name' => 'Latvian lats',
                'symbol' => '&#8364;'
            ),

            array(
                'code' => 'LBP',
                'countryname' => 'Lebanon',
                'name' => 'Lebanese pound',
                'symbol' => '&#76;&#163;'
            ),

            array(
                'code' => 'LRD',
                'countryname' => 'Liberia',
                'name' => 'Liberian dollar',
                'symbol' => '&#76;&#68;&#36;'
            ),

            array(
                'code' => 'LTL',
                'countryname' => 'Lithuania',
                'name' => 'Lithuanian litas',
                'symbol' => '&#8364;'
            ),

            array(
                'code' => 'MKD',
                'countryname' => 'North Macedonia',
                'name' => 'Macedonian denar',
                'symbol' => '&#1076;&#1077;&#1085;'
            ),

            array(
                'code' => 'MYR',
                'countryname' => 'Malaysia',
                'name' => 'Malaysian ringgit',
                'symbol' => '&#82;&#77;'
            ),

            array(
                'code' => 'MUR',
                'countryname' => 'Mauritius',
                'name' => 'Mauritian rupee',
                'symbol' => '&#82;&#115;'
            ),

            array(
                'code' => 'MXN',
                'countryname' => 'Mexico',
                'name' => 'Mexican peso',
                'symbol' => '&#77;&#101;&#120;&#36;'
            ),

            array(
                'code' => 'MNT',
                'countryname' => 'Mongolia',
                'name' => 'Mongolian tögrög',
                'symbol' => '&#8366;'
            ),


            array(
                'code' => 'MZN',
                'countryname' => 'Mozambique',
                'name' => 'Mozambican metical',
                'symbol' => '&#77;&#84;'
            ),

            array(
                'code' => 'NAD',
                'countryname' => 'Namibia',
                'name' => 'Namibian dollar',
                'symbol' => '&#78;&#36;'
            ),

            array(
                'code' => 'NPR',
                'countryname' => 'Federal Democratic Republic of Nepal',
                'name' => 'Nepalese rupee',
                'symbol' => '&#82;&#115;&#46;'
            ),

            array(
                'code' => 'ANG',
                'countryname' => 'Curaçao and Sint Maarten',
                'name' => 'Netherlands Antillean guilder',
                'symbol' => '&#402;'
            ),

            array(
                'code' => 'NZD',
                'countryname' => 'New Zealand, the Cook Islands, Niue, the Ross Dependency, Tokelau, the Pitcairn Islands',
                'name' => 'New Zealand dollar',
                'symbol' => '&#36;'
            ),


            array(
                'code' => 'NIO',
                'countryname' => 'Nicaragua',
                'name' => 'Nicaraguan córdoba',
                'symbol' => '&#67;&#36;'
            ),

            array(
                'code' => 'NGN',
                'countryname' => 'Nigeria',
                'name' => 'Nigerian naira',
                'symbol' => '&#8358;'
            ),

            array(
                'code' => 'NOK',
                'countryname' => 'Norway and its dependent territories',
                'name' => 'Norwegian krone',
                'symbol' => '&#107;&#114;'
            ),

            array(
                'code' => 'OMR',
                'countryname' => 'Oman',
                'name' => 'Omani rial',
                'symbol' => '&#65020;'
            ),

            array(
                'code' => 'PKR',
                'countryname' => 'Pakistan',
                'name' => 'Pakistani rupee',
                'symbol' => '&#82;&#115;'
            ),

            array(
                'code' => 'PAB',
                'countryname' => 'Panama',
                'name' => 'Panamanian balboa',
                'symbol' => '&#66;&#47;&#46;'
            ),

            array(
                'code' => 'PYG',
                'countryname' => 'Paraguay',
                'name' => 'Paraguayan Guaraní',
                'symbol' => '&#8370;'
            ),

            array(
                'code' => 'PEN',
                'countryname' => 'Peru',
                'name' => 'Sol',
                'symbol' => '&#83;&#47;&#46;'
            ),

            array(
                'code' => 'PHP',
                'countryname' => 'Philippines',
                'name' => 'Philippine peso',
                'symbol' => '&#8369;'
            ),

            array(
                'code' => 'PLN',
                'countryname' => 'Poland',
                'name' => 'Polish złoty',
                'symbol' => '&#122;&#322;'
            ),

            array(
                'code' => 'QAR',
                'countryname' => 'State of Qatar',
                'name' => 'Qatari Riyal',
                'symbol' => '&#65020;'
            ),

            array(
                'code' => 'RON',
                'countryname' => 'Romania',
                'name' => 'Romanian leu (Leu românesc)',
                'symbol' => '&#76;'
            ),

            array(
                'code' => 'RUB',
                'countryname' => 'Russian Federation, Abkhazia and South Ossetia, Donetsk and Luhansk',
                'name' => 'Russian ruble',
                'symbol' => '&#8381;'
            ),


            array(
                'code' => 'SHP',
                'countryname' => 'Saint Helena, Ascension and Tristan da Cunha',
                'name' => 'Saint Helena pound',
                'symbol' => '&#163;'
            ),

            array(
                'code' => 'SAR',
                'countryname' => 'Saudi Arabia',
                'name' => 'Saudi riyal',
                'symbol' => '&#65020;'
            ),

            array(
                'code' => 'RSD',
                'countryname' => 'Serbia',
                'name' => 'Serbian dinar',
                'symbol' => '&#100;&#105;&#110;'
            ),

            array(
                'code' => 'SCR',
                'countryname' => 'Seychelles',
                'name' => 'Seychellois rupee',
                'symbol' => '&#82;&#115;'
            ),

            array(
                'code' => 'SGD',
                'countryname' => 'Singapore',
                'name' => 'Singapore dollar',
                'symbol' => '&#83;&#36;'
            ),

            array(
                'code' => 'SBD',
                'countryname' => 'Solomon Islands',
                'name' => 'Solomon Islands dollar',
                'symbol' => '&#83;&#73;&#36;'
            ),

            array(
                'code' => 'SOS',
                'countryname' => 'Somalia',
                'name' => 'Somali shilling',
                'symbol' => '&#83;&#104;&#46;&#83;&#111;'
            ),

            array(
                'code' => 'ZAR',
                'countryname' => 'South Africa',
                'name' => 'South African rand',
                'symbol' => '&#82;'
            ),

            array(
                'code' => 'LKR',
                'countryname' => 'Sri Lanka',
                'name' => 'Sri Lankan rupee',
                'symbol' => '&#82;&#115;'
            ),


            array(
                'code' => 'SEK',
                'countryname' => 'Sweden',
                'name' => 'Swedish krona',
                'symbol' => '&#107;&#114;'
            ),


            array(
                'code' => 'CHF',
                'countryname' => 'Switzerland',
                'name' => 'Swiss franc',
                'symbol' => '&#67;&#72;&#102;'
            ),

            array(
                'code' => 'SRD',
                'countryname' => 'Suriname',
                'name' => 'Suriname Dollar',
                'symbol' => '&#83;&#114;&#36;'
            ),

            array(
                'code' => 'SYP',
                'countryname' => 'Syria',
                'name' => 'Syrian pound',
                'symbol' => '&#163;&#83;'
            ),

            array(
                'code' => 'TWD',
                'countryname' => 'Taiwan',
                'name' => 'New Taiwan dollar',
                'symbol' => '&#78;&#84;&#36;'
            ),


            array(
                'code' => 'THB',
                'countryname' => 'Thailand',
                'name' => 'Thai baht',
                'symbol' => '&#3647;'
            ),


            array(
                'code' => 'TTD',
                'countryname' => 'Trinidad and Tobago',
                'name' => 'Trinidad and Tobago dollar',
                'symbol' => '&#84;&#84;&#36;'
            ),


            array(
                'code' => 'TRY',
                'countryname' => 'Turkey, Turkish Republic of Northern Cyprus',
                'name' => 'Turkey Lira',
                'symbol' => '&#8378;'
            ),

            array(
                'code' => 'TVD',
                'countryname' => 'Tuvalu',
                'name' => 'Tuvaluan dollar',
                'symbol' => '&#84;&#86;&#36;'
            ),

            array(
                'code' => 'UAH',
                'countryname' => 'Ukraine',
                'name' => 'Ukrainian hryvnia',
                'symbol' => '&#8372;'
            ),


            array(
                'code' => 'GBP',
                'countryname' => 'United Kingdom, Jersey, Guernsey, the Isle of Man, Gibraltar, South Georgia and the South Sandwich Islands, the British Antarctic Territory, and Tristan da Cunha',
                'name' => 'Pound sterling',
                'symbol' => '&#163;'
            ),


            array(
                'code' => 'UGX',
                'countryname' => 'Uganda',
                'name' => 'Ugandan shilling',
                'symbol' => '&#85;&#83;&#104;'
            ),


            array(
                'code' => 'USD',
                'countryname' => 'United States',
                'name' => 'United States dollar',
                'symbol' => '&#36;'
            ),

            array(
                'code' => 'UYU',
                'countryname' => 'Uruguayan',
                'name' => 'Peso Uruguayolar',
                'symbol' => '&#36;&#85;'
            ),

            array(
                'code' => 'UZS',
                'countryname' => 'Uzbekistan',
                'name' => 'Uzbekistani soʻm',
                'symbol' => '&#1083;&#1074;'
            ),


            array(
                'code' => 'VEF',
                'countryname' => 'Venezuela',
                'name' => 'Venezuelan bolívar',
                'symbol' => '&#66;&#115;'
            ),


            array(
                'code' => 'VND',
                'countryname' => 'Vietnam',
                'name' => 'Vietnamese dong (Đồng)',
                'symbol' => '&#8363;'
            ),

            array(
                'code' => 'VND',
                'countryname' => 'Yemen',
                'name' => 'Yemeni rial',
                'symbol' => '&#65020;'
            ),

            array(
                'code' => 'ZWD',
                'countryname' => 'Zimbabwe',
                'name' => 'Zimbabwean dollar',
                'symbol' => '&#90;&#36;'
            ),
        );
        return response()->json([
            "status" => true,
            "message" => "All currency data retrieved successfully.",
            "data" => $currencies
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
