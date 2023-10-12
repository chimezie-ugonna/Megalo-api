<?php

namespace App\Http\Controllers;

use App\Custom\Authentication;
use App\Custom\EmailManager;
use App\Custom\IdentityVerifier;
use App\Custom\Localization;
use App\Custom\MediaManager;
use App\Custom\NotificationManager;
use Illuminate\Http\Request;
use App\Custom\PaymentManager;
use App\Custom\SmsManager;
use App\Custom\WebSocket;
use App\Models\Earning;
use App\Models\Investment;
use App\Models\Notification;
use App\Models\Property;
use App\Models\Referral;
use App\Models\User;
use DateTime;
use Jenssegers\Agent\Agent;

class UserController extends Controller
{
    public function sendOtp(Request $request)
    {
        /*if ($request->request->get("type") == "email") {
            $app_language_code = User::find($request->request->get("user_id"))->login()->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->value("app_language_code");
            $localization = new Localization($app_language_code, []);
            $subject = $localization->getText("verification_email_subject");
            $title = $localization->getText("verification_email_title");
            $body = $localization->getText("verification_email_body");
            $footer = $localization->getText("verification_email_footer");

            $send = new EmailManager();
            $status = $send->sendOtp($request->request->get("email"), [
                "subject" => $subject,
                "title" => $title,
                "body" => $body,
                "footer" => $footer
            ]);
        } else {
            if ($request->request->has("update") && $request->filled("update") && $request->request->get("update") && User::where("user_id", "!=", $request->request->get("user_id"))->where("phone_number", $request->request->get("phone_number"))->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "The phone number provided has been taken."
                ], 409);
            }
            $send = new SmsManager();
            $status = $send->sendOtp($request->request->get("phone_number"));
        }
        if (isset($status) && isset($status->status) && $status->status == "pending") {
            return response()->json([
                "status" => true,
                "message" => "Otp was successfully sent."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to send otp."
            ], 500);
        }*/

        if ($request->request->get("type") == "sms") {
            if ($request->request->has("update") && $request->filled("update") && $request->request->get("update") && User::where("user_id", "!=", $request->request->get("user_id"))->where("phone_number", $request->request->get("phone_number"))->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "The phone number provided has been taken."
                ], 409);
            }
        }

        return response()->json([
            "status" => true,
            "message" => "The otp was not sent because our twilio credit is exhausted. But for testing purposes, this response is successful."
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $websocket = new WebSocket();
        /*if ($request->request->get("type") == "email") {
            $send = new EmailManager();
            $status = $send->verifyOtp($request->request->get("email"), $request->request->get("otp"));
        } else {
            $send = new SmsManager();
            $status = $send->verifyOtp($request->request->get("phone_number"), $request->request->get("otp"));
        }
        if (isset($status) && isset($status->status)) {
            if ($status->status == "approved") {
                $data = [];
                $message = "Otp was successfully verified.";
                if ($request->request->get("type") == "email") {
                    User::find($request->request->get("user_id"))->update(["email" => $request->request->get("email"), "email_verified" => true]);
                    $websocket->trigger(["user_id" => $request->request->get("user_id"), "type" => "email_verification", "email" => $request->request->get("email"), "status" => true]);
                    $message = "Otp was successfully verified and email was updated successfully.";
                } else {
                    if ($request->request->has("update") && $request->filled("update") && $request->request->get("update")) {
                        User::find($request->request->get("user_id"))->update(["phone_number" => $request->request->get("phone_number")]);
                        $message = "Otp was successfully verified and phone number was updated successfully.";
                    } else {
                        $auth = new Authentication();
                        $data["token"] = $auth->encode($request->request->get("phone_number"));
                        if (User::where("phone_number", $request->request->get("phone_number"))->exists()) {
                            $data["user_exists"] = true;
                            $data["is_admin"] = User::where("phone_number", $request->request->get("phone_number"))->value("is_admin");
                        } else {
                            $data["user_exists"] = false;
                        }
                    }
                }
                return response()->json([
                    "status" => true,
                    "message" => $message,
                    "data" => $data
                ], 200);
            } else if ($status->status == "pending") {
                return response()->json([
                    "status" => false,
                    "message" => "The otp verification was unsuccessful. Code is incorrect."
                ], 403);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "The otp verification was unsuccessful. Something went wrong."
                ], 500);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "A failure occurred while trying to verify otp."
            ], 500);
        }*/

        $data = [];
        $message = "The otp was not verified because our twilio credit is exhausted. But for testing purposes, this response is successful.";
        if ($request->request->get("type") == "email") {
            User::find($request->request->get("user_id"))->update(["email" => $request->request->get("email"), "email_verified" => true]);
            $websocket->trigger(["user_id" => $request->request->get("user_id"), "type" => "email_verification", "email" => $request->request->get("email"), "status" => true]);
            $message = "The otp was not verified because our twilio credit is exhausted. But for testing purposes, this response is successful and email was updated successfully.";
        } else {
            if ($request->request->has("update") && $request->filled("update") && $request->request->get("update")) {
                User::find($request->request->get("user_id"))->update(["phone_number" => $request->request->get("phone_number")]);
                $message = "The otp was not verified because our twilio credit is exhausted. But for testing purposes, this response is successful and phone number was updated successfully.";
            } else {
                $auth = new Authentication();
                $data = array("token" => $auth->encode($request->request->get("phone_number")));
                if (User::where("phone_number", $request->request->get("phone_number"))->exists()) {
                    $data["user_exists"] = true;
                    $data["is_admin"] = User::where("phone_number", $request->request->get("phone_number"))->value("is_admin");
                } else {
                    $data["user_exists"] = false;
                }
            }
        }
        return response()->json([
            "status" => true,
            "message" => $message,
            "data" => $data
        ], 200);
    }

    public function create(Request $request)
    {
        $auth = new Authentication();
        if (!User::where("user_id", $request->request->get("user_id"))->exists()) {
            $has_referral = false;
            if ($request->request->has("referral_code") && $request->filled("referral_code")) {
                if (User::where("referral_code", $request->request->get("referral_code"))->exists()) {
                    $referrer_phone_number = User::where("referral_code", $request->request->get("referral_code"))->value("phone_number");
                    $referrer_user_id = User::where("referral_code", $request->request->get("referral_code"))->value("user_id");
                    $referree_phone_number = $request->request->get("phone_number");
                    $referree_user_id = $request->request->get("user_id");
                    if (!Referral::where("referree_phone_number", $referree_phone_number)->exists() && !Referral::where("referrer_phone_number", $referree_phone_number)->exists()) {
                        $has_referral = true;
                    }
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "Invalid referral code."
                    ], 404);
                }
            }

            do {
                $alphabets = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
                $referral_code = "";
                for ($i = 0; $i < 3; $i++) {
                    $referral_code .= $alphabets[rand(0, 25)] . rand(0, 9);
                }
            } while (User::where("referral_code", $referral_code)->exists());
            $request->request->add(["referral_code" => $referral_code]);
            User::create($request->all());
            User::find($request->request->get("user_id"))->login()->updateOrCreate(["user_id" => $request->request->get("user_id"), "access_type" => $request->request->get("access_type"), "device_os" => $request->request->get("device_os"), "device_token" => $request->request->get("device_token")], $request->all());
            if ($has_referral) {
                Referral::create(["referrer_phone_number" => $referrer_phone_number, "referrer_user_id" => $referrer_user_id, "referree_phone_number" => $referree_phone_number, "referree_user_id" => $referree_user_id]);
            }
            return response()->json([
                "status" => true,
                "message" => "User registered successfully.",
                "data" => ["token" => $auth->encode($request->request->get("user_id"))]
            ], 201);
        } else {
            return response()->json([
                "status" => true,
                "message" => "User already registered successfully.",
                "data" => ["token" => $auth->encode($request->request->get("user_id"))]
            ], 200);
        }
    }

    public function read(Request $request)
    {
        if (User::where("user_id", $request->request->get("user_id"))->exists()) {
            $data = collect(User::where("user_id", $request->request->get("user_id"))->get());
            $data = $data->map(function ($item) use ($request) {
                $has_unseen_notification = Notification::where("receiver_user_id", $request->request->get("user_id"))->where("seen", false)->exists();
                $item->has_unseen_notification = $has_unseen_notification;
                $item->pusher_app_key = getenv("PUSHER_APP_KEY");
                $item->pusher_app_cluster = getenv("PUSHER_APP_CLUSTER");
                $item->pusher_channel_name = getenv("PUSHER_CHANNEL_NAME");
                $item->pusher_event_name = getenv("PUSHER_EVENT_NAME");
                return $item;
            });
            return response()->json([
                "status" => true,
                "message" => "User data retrieved successfully.",
                "data" => $data
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User data not found."
            ], 404);
        }
    }

    public function readSpecific(Request $request)
    {
        if (User::where("user_id", $request->get("user_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "User data retrieved successfully.",
                "data" => User::where("user_id", $request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "User data not found."
            ], 404);
        }
    }

    public function readAll(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All user data retrieved successfully.",
            "data" => User::latest()->simplePaginate($request->get("limit"))
        ], 200);
    }

    public function readEarning(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "User earning data retrieved successfully.",
            "data" => Earning::where("user_id", $request->request->get("user_id"))->where("property_id", $request->get("property_id"))->latest()->simplePaginate($request->get("limit"))
        ], 200);
    }

    public function readAllEarning(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All user earning data retrieved successfully.",
            "data" => Earning::where("user_id", $request->request->get("user_id"))->latest()->simplePaginate($request->get("limit"))
        ], 200);
    }

    public function readDashboardData()
    {
        return response()->json([
            "status" => true,
            "message" => "Dashboard data retrieved successfully.",
            "data" => ["user_count" => User::count(), "property_count" => Property::count(), "investor_count" => count(Investment::all()->pluck("user_id")->unique()), "all_property_value" => Property::all()->sum("value_usd"), "all_investment_amount" => Investment::all()->sum("amount_invested_usd")]
        ], 200);
    }

    public function readReferralData(Request $request)
    {
        $payment_manager = new PaymentManager();
        return response()->json([
            "status" => true,
            "message" => "Referral data retrieved successfully.",
            "data" => ["total_referral" => Referral::where("referrer_user_id", $request->request->get("user_id"))->count(), "total_rewarded" => Referral::where("referrer_user_id", $request->request->get("user_id"))->where("rewarded", true)->count(), "total_pending" => Referral::where("referrer_user_id", $request->request->get("user_id"))->where("rewarded", false)->count(), "total_reward" => Referral::where("referrer_user_id", $request->request->get("user_id"))->sum("reward_usd"), "reward_amount" => $payment_manager->getReferralBonusUsd(), "reward_minimum_investment_amount" => $payment_manager->getReferralBonusMinimumInvestmentAmountUsd(), "referral_code" => User::where("user_id", $request->request->get("user_id"))->value("referral_code"), "minimum_investment_amount" => $payment_manager->getMinimumInvestmentAmountUsd()]
        ], 200);
    }

    public function readReferree(Request $request)
    {
        if ($request->has("type") && $request->filled("type")) {
            $rewarded = false;
            if ($request->get("type") == "completed") {
                $rewarded = true;
            }
            $data = collect(Referral::where("referrer_user_id", $request->request->get("user_id"))->where("rewarded", $rewarded)->join("users", "users.user_id", "=", "referrals.referree_user_id")->select("referrals.*", "users.first_name", "users.last_name")->latest()->simplePaginate($request->get("limit")));
        } else {
            $data = collect(Referral::where("referrer_user_id", $request->request->get("user_id"))->join("users", "users.user_id", "=", "referrals.referree_user_id")->select("referrals.*", "users.first_name", "users.last_name")->latest()->simplePaginate($request->get("limit")));
        }
        $data->put("user_id", $request->request->get("user_id"));
        $data->put("pusher_app_key", getenv("PUSHER_APP_KEY"));
        $data->put("pusher_app_cluster", getenv("PUSHER_APP_CLUSTER"));
        $data->put("pusher_channel_name", getenv("PUSHER_CHANNEL_NAME"));
        $data->put("pusher_event_name", getenv("PUSHER_EVENT_NAME"));
        return response()->json([
            "status" => true,
            "message" => "Referree data retrieved successfully.",
            "data" => $data
        ], 200);
    }

    public function verifyIdentity(Request $request)
    {
        if (User::where("user_id", $request->request->get("user_id"))->value("identity_verification_status") != "verified") {
            $identity_verifier = new IdentityVerifier();
            $response = json_decode($identity_verifier->run("generateToken", $request->request->get("user_id")), true);
            if (isset($response) && isset($response["authToken"])) {
                return response()->json([
                    "status" => true,
                    "message" => "Identity verification process initiated.",
                    "data" => ["auth_token" => $response["authToken"]]
                ], 200);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "An error occurred while initiating identity verification, identity verification initiation failed."
                ], 500);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "This user's identity has already been verified.",
            ], 403);
        }
    }

    public function verifyIdentityWebhook(Request $request)
    {
        if (User::where("user_id", $request->request->get("clientId"))->exists() && User::where("user_id", $request->request->get("clientId"))->value("identity_verification_status") != "verified") {
            $websocket = new WebSocket();
            if ($request->request->get("final") && $request->request->get("status")["overall"] == "APPROVED" || $request->request->get("final") && $request->request->get("status")["overall"] == "DENIED" || $request->request->get("final") && $request->request->get("status")["overall"] == "SUSPECTED") {
                $status = "verified";
                $body_key = "identity_verification_success_body";
                if ($request->request->get("status")["overall"] == "APPROVED") {
                    $today = new DateTime(date("Y-m-d"));
                    $bday = new DateTime($request->request->get("data")["docDob"]);
                    $interval = $today->diff($bday);
                    $age_estimate = $request->request->get("data")["ageEstimate"];
                    if (intval($interval->y) < 18) {
                        $status = "under_age";
                        $body_key = "identity_verification_success_under_age";
                    } else if ($age_estimate == "UNDER_13") {
                        $status = "under_age";
                        $body_key = "identity_verification_success_estimate_under_age";
                    } else {
                        $date_obj = DateTime::createFromFormat("Y-m-d", $request->request->get("data")["docDob"]);
                        $dob = $date_obj->format("d/m/Y");
                        $media_manager = new MediaManager();
                        $data = $media_manager->uploadMedia("image", $request->request->get("fileUrls")["FACE"], "users");
                        if (isset($data) && isset($data["url"]) && isset($data["public_id"])) {
                            if (!User::where("user_id", "!=", $request->request->get("clientId"))->where("identity_verification_status", "verified")->where("first_name", ucwords(strtolower($request->request->get("data")["docFirstName"])))->where("last_name", ucwords(strtolower($request->request->get("data")["docLastName"])))->where("dob", $dob)->where("gender", ucwords(strtolower($request->request->get("data")["docSex"])))->exists()) {
                                User::find($request->request->get("clientId"))->update(["first_name" => ucwords(strtolower($request->request->get("data")["docFirstName"])), "last_name" => ucwords(strtolower($request->request->get("data")["docLastName"])), "dob" => $dob, "gender" => strtolower($request->request->get("data")["docSex"]), "nationality" => $request->request->get("data")["docNationality"], "image_url" => $data["url"] . "+ " . $data["public_id"], "identity_verification_status" => "verified", "identity_verification_id" => $request->request->get("scanRef")]);
                            } else {
                                $status = "duplicate";
                                $body_key = "identity_verification_success_duplicate_verification";
                            }
                        } else {
                            $status = "unverified";
                            $body_key = "identity_verification_failed_image_upload_error";
                        }
                    }
                } else if ($request->request->get("status")["overall"] == "DENIED") {
                    $status = "unverified";
                    $body_key = "identity_verification_failed_body";
                } else if ($request->request->get("status")["overall"] == "SUSPECTED") {
                    if ($request->request->get("status")["autoDocument"] == "DOC_VALIDATED" && $request->request->get("status")["manualDocument"] == "DOC_VALIDATED" && $request->request->get("status")["autoFace"] == "FACE_MATCH" && $request->request->get("status")["manualFace"] == "FACE_MATCH") {
                        $today = new DateTime(date("Y-m-d"));
                        $bday = new DateTime($request->request->get("data")["docDob"]);
                        $interval = $today->diff($bday);
                        $age_estimate = $request->request->get("data")["ageEstimate"];
                        if (intval($interval->y) < 18) {
                            $status = "under_age";
                            $body_key = "identity_verification_success_under_age";
                        } else if ($age_estimate == "UNDER_13") {
                            $status = "under_age";
                            $body_key = "identity_verification_success_estimate_under_age";
                        } else {
                            $date_obj = DateTime::createFromFormat("Y-m-d", $request->request->get("data")["docDob"]);
                            $dob = $date_obj->format("d/m/Y");
                            $media_manager = new MediaManager();
                            $data = $media_manager->uploadMedia("image", $request->request->get("fileUrls")["FACE"], "users");
                            if (isset($data) && isset($data["url"]) && isset($data["public_id"])) {
                                if (!User::where("user_id", "!=", $request->request->get("clientId"))->where("identity_verification_status", "verified")->where("first_name", ucwords(strtolower($request->request->get("data")["docFirstName"])))->where("last_name", ucwords(strtolower($request->request->get("data")["docLastName"])))->where("dob", $dob)->where("gender", ucwords(strtolower($request->request->get("data")["docSex"])))->exists()) {
                                    User::find($request->request->get("clientId"))->update(["first_name" => ucwords(strtolower($request->request->get("data")["docFirstName"])), "last_name" => ucwords(strtolower($request->request->get("data")["docLastName"])), "dob" => $dob, "gender" => strtolower($request->request->get("data")["docSex"]), "nationality" => $request->request->get("data")["docNationality"], "image_url" => $data["url"] . "+ " . $data["public_id"], "identity_verification_status" => "verified", "identity_verification_id" => $request->request->get("scanRef")]);
                                } else {
                                    $status = "duplicate";
                                    $body_key = "identity_verification_success_duplicate_verification";
                                }
                            } else {
                                $status = "unverified";
                                $body_key = "identity_verification_failed_image_upload_error";
                            }
                        }
                    } else {
                        $status = "unverified";
                        $body_key = "identity_verification_failed_body";
                    }
                }

                if ($status != "verified") {
                    User::find($request->request->get("clientId"))->update(["identity_verification_status" => "unverified"]);
                    $websocket->trigger(["user_id" => $request->request->get("clientId"), "type" => "identity_verification", "status" => "unverified"]);
                }

                $notification_manager = new NotificationManager();
                if ($status == "unverified") {
                    $title_key = "identity_verification_failed_title";
                } else {
                    $title_key = "identity_verification_success_title";
                }
                $notification_manager->sendNotification(array(
                    "receiver_user_id" => $request->request->get("clientId"),
                    "title_key" => $title_key,
                    "body_key" => $body_key,
                    "tappable" => false,
                    "redirection_page" => "",
                    "redirection_page_id" => ""
                ), array(), "user_specific");
            } else if (User::where("user_id", $request->request->get("clientId"))->value("identity_verification_status") != "pending") {
                User::find($request->request->get("clientId"))->update(["identity_verification_status" => "pending"]);
                $websocket->trigger(["user_id" => $request->request->get("clientId"), "type" => "identity_verification", "status" => "pending"]);
            }
        }
        return response()->json([
            "status" => true,
            "message" => "Callback received successfully."
        ], 200);
    }

    public function redirectAppDownload()
    {
        $agent = new Agent();
        if ($agent->isMobile()) {
            if ($agent->isiOS()) {
                return redirect("https://apps.apple.com/ng/app/google-more-ways-to-search/id284815942");
            } else if ($agent->isAndroidOS()) {
                return redirect("https://play.google.com/store/apps/details?id=com.google.android.googlequicksearchbox&pcampaignid=web_share");
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Device is unsupported."
                ], 400);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Device is unsupported."
            ], 400);
        }
    }

    public function update(Request $request)
    {
        if ($request->request->has("first_name") && $request->filled("first_name") || $request->request->has("last_name") && $request->filled("last_name") || $request->request->has("dob") && $request->filled("dob")) {
            if (User::where("user_id", $request->request->get("user_id"))->value("identity_verification_status") == "verified") {
                return response()->json([
                    "status" => false,
                    "message" => "This user's identity has been verified so they can not update their name or dob.",
                ], 403);
            }
        }
        User::find($request->request->get("user_id"))->update($request->all());
        $websocket = new WebSocket();
        $websocket->trigger(["user_id" => $request->request->get("user_id"), "type" => "user_data_update", "first_name" => $request->request->get("first_name"), "last_name" => $request->request->get("last_name")]);
        return response()->json([
            "status" => true,
            "message" => "User data updated successfully.",
        ], 200);
    }

    public function delete(Request $request)
    {
        if (User::where("user_id", $request->request->get("user_id"))->value("balance_usd") == 0) {
            $status = true;
            $payment_manager = new PaymentManager();
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                $customer_response = $payment_manager->manage(array("type" => "delete_customer", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id")));
                if (isset($customer_response) && isset($customer_response["deleted"]) && $customer_response["deleted"]) {
                    User::find($request->request->get("user_id"))->update(["payment_customer_id" => ""]);
                } else {
                    $status = false;
                }
            }
            if ($status && User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                $account_response = $payment_manager->manage(array("type" => "delete_account", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id")));
                if (isset($account_response) && isset($account_response["deleted"]) && $account_response["deleted"]) {
                    User::find($request->request->get("user_id"))->update(["payment_account_id" => ""]);
                } else {
                    $status = false;
                }
            }
            if ($status && User::where("user_id", $request->request->get("user_id"))->value("image_url") != "") {
                $media_manager = new MediaManager();
                $data = explode("+ ", User::where("user_id", $request->request->get("user_id"))->value("image_url"));
                if (count($data) > 1) {
                    $data = $media_manager->deleteMedia("image", $data[1]);
                    if (!isset($data) || !isset($data["result"]) || $data["result"] != "ok") {
                        $status = false;
                    } else {
                        User::find($request->request->get("user_id"))->update(["image_url" => ""]);
                    }
                }
            }
            if ($status && User::where("user_id", $request->request->get("user_id"))->value("identity_verification_id") != "") {
                $identity_verifier = new IdentityVerifier();
                $response = $identity_verifier->run("deleteVerification", User::where("user_id", $request->request->get("user_id"))->value("identity_verification_id"));
                if ($response == "") {
                    User::find($request->request->get("user_id"))->update(["identity_verification_id" => ""]);
                } else {
                    $status = false;
                }
            }

            if ($status) {
                User::find($request->request->get("user_id"))->login()->delete();
                User::find($request->request->get("user_id"))->investment()->delete();
                User::find($request->request->get("user_id"))->notificationSender()->delete();
                User::find($request->request->get("user_id"))->notificationReceiver()->delete();
                User::find($request->request->get("user_id"))->payment()->delete();
                User::find($request->request->get("user_id"))->earning()->delete();
                User::find($request->request->get("user_id"))->failedWithdrawal()->delete();
                User::destroy($request->request->get("user_id"));
                return response()->json([
                    "status" => true,
                    "message" => "User deleted successfully."
                ], 200);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "An error occurred while deleting user, user could not be deleted."
                ], 500);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "User's balance has to be zero before this process can be completed."
            ], 403);
        }
    }

    public function createPaymentMethod(Request $request)
    {
        $user_identity_verification_status = User::where("user_id", $request->request->get("user_id"))->value("identity_verification_status");
        if ($user_identity_verification_status == "verified") {
            $user_email_verified = User::where("user_id", $request->request->get("user_id"))->value("email_verified");
            if ($user_email_verified) {
                $payment_manager = new PaymentManager();
                $create_token_response = $payment_manager->manage(array("type" => "create_token", "data" => $request->all()));
                if (isset($create_token_response) && isset($create_token_response["id"])) {
                    $token = $create_token_response["id"];
                    $add_payment_method_response = null;
                    if ($request->request->get("action") == "deposit") {
                        if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                            $list_all_payment_method_response = $payment_manager->manage(array("type" => "list_all_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["type" => $create_token_response["type"]]));
                            foreach ($list_all_payment_method_response->data as $data) {
                                if (
                                    $data->object == "card" &&
                                    $data->object == $create_token_response["type"] &&
                                    $data->brand == $create_token_response->card->brand &&
                                    $data->fingerprint == $create_token_response->card->fingerprint &&
                                    $data->exp_month == $create_token_response->card->exp_month &&
                                    $data->exp_year == $create_token_response->card->exp_year &&
                                    $data->last4 == $create_token_response->card->last4
                                ) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "This card has already been added."
                                    ], 400);
                                } else if (
                                    $data->object == "bank_account" &&
                                    $data->object == $create_token_response["type"] &&
                                    $data->bank_name == $create_token_response->bank_account->bank_name &&
                                    $data->fingerprint == $create_token_response->bank_account->fingerprint &&
                                    $data->routing_number == $create_token_response->bank_account->routing_number &&
                                    $data->last4 == $create_token_response->bank_account->last4
                                ) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "This bank account has already been added."
                                    ], 400);
                                }
                            }
                            $add_payment_method_response = $payment_manager->manage(array("type" => "add_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["token" => $token]));
                        } else {
                            $customer_response = $payment_manager->manage(array("type" => "create_customer", "data" => ["full_name" => User::where("user_id", $request->request->get("user_id"))->value("first_name") . " " . User::where("user_id", $request->request->get("user_id"))->value("last_name")]));
                            if (isset($customer_response) && isset($customer_response["id"])) {
                                User::find($request->request->get("user_id"))->update(["payment_customer_id" => $customer_response["id"]]);
                                $add_payment_method_response = $payment_manager->manage(array("type" => "add_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["token" => $token]));
                            } else {
                                return response()->json([
                                    "status" => false,
                                    "message" => "An error occurred while adding payment method, payment method could not be added."
                                ], 500);
                            }
                        }
                    } else if ($request->request->get("action") == "withdrawal") {
                        if (User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                            $list_all_payment_method_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["type" => $create_token_response["type"]]));
                            foreach ($list_all_payment_method_response->data as $data) {
                                if (
                                    $data->object == "card" &&
                                    $data->object == $create_token_response["type"] &&
                                    $data->brand == $create_token_response->card->brand &&
                                    $data->fingerprint == $create_token_response->card->fingerprint &&
                                    $data->exp_month == $create_token_response->card->exp_month &&
                                    $data->exp_year == $create_token_response->card->exp_year &&
                                    $data->last4 == $create_token_response->card->last4
                                ) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "This card has already been added."
                                    ], 400);
                                } else if (
                                    $data->object == "bank_account" &&
                                    $data->object == $create_token_response["type"] &&
                                    $data->bank_name == $create_token_response->bank_account->bank_name &&
                                    $data->fingerprint == $create_token_response->bank_account->fingerprint &&
                                    $data->routing_number == $create_token_response->bank_account->routing_number &&
                                    $data->last4 == $create_token_response->bank_account->last4
                                ) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "This bank account has already been added."
                                    ], 400);
                                }
                            }
                            $add_payment_method_response = $payment_manager->manage(array("type" => "add_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["token" => $token]));
                        } else {
                            $dob = User::where("user_id", $request->request->get("user_id"))->value("dob");
                            $account_response = $payment_manager->manage(array("type" => "create_account", "data" => ["first_name" => User::where("user_id", $request->request->get("user_id"))->value("first_name"), "last_name" => User::where("user_id", $request->request->get("user_id"))->value("last_name"), "gender" => User::where("user_id", $request->request->get("user_id"))->value("gender"), "day_of_birth" => date("j", strtotime(date("d/m/Y", strtotime($dob)))), "month_of_birth" => date("n", strtotime(date("d/m/Y", strtotime($dob)))), "year_of_birth" => date("Y", strtotime(date("d/m/Y", strtotime($dob)))), "time_stamp" => strtotime(date("Y-m-d H:i:s")), "ip_address" => User::find($request->request->get("user_id"))->login()->where("access_type", $request->header("access-type"))->where("device_os", $request->header("device-os", ""))->where("device_token", $request->header("device-token", ""))->value("ip_address")]));
                            if (isset($account_response) && isset($account_response["id"])) {
                                User::find($request->request->get("user_id"))->update(["payment_account_id" => $account_response["id"]]);
                                $add_payment_method_response = $payment_manager->manage(array("type" => "add_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["token" => $token]));
                            } else {
                                return response()->json([
                                    "status" => false,
                                    "message" => "An error occurred while adding payment method, payment method could not be added."
                                ], 500);
                            }
                        }
                    }
                    if (isset($add_payment_method_response) && isset($add_payment_method_response["id"])) {
                        if ($request->request->get("action") == "deposit" && $request->request->get("type") == "bank_account") {
                            $verify_customer_bank_account_response = $payment_manager->manage(array("type" => "verify_customer_bank_account", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["id" => $add_payment_method_response["id"]]));
                            if (isset($verify_customer_bank_account_response) && isset($verify_customer_bank_account_response["status"]) && $verify_customer_bank_account_response["status"] == "verified") {
                                return response()->json([
                                    "status" => true,
                                    "message" => "Payment method added successfully."
                                ], 201);
                            } else {
                                $delete_customer_payment_method_response = $payment_manager->manage(array("type" => "delete_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["id" => $add_payment_method_response["id"]]));
                                if (isset($delete_customer_payment_method_response) && isset($delete_customer_payment_method_response["deleted"]) && $delete_customer_payment_method_response["deleted"]) {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "An error occurred while verifying payment method, payment method could not be added."
                                    ], 500);
                                } else {
                                    return response()->json([
                                        "status" => false,
                                        "message" => "An error occurred while verifying payment method and while attempting to delete payment method."
                                    ], 500);
                                }
                            }
                        } else {
                            return response()->json([
                                "status" => true,
                                "message" => "Payment method added successfully."
                            ], 201);
                        }
                    } else {
                        return response()->json([
                            "status" => false,
                            "message" => "An error occurred while adding payment method, payment method could not be added."
                        ], 500);
                    }
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "An error occurred while adding payment method, payment method could not be added."
                    ], 500);
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User email has to be verified before any payment method can be added."
                ], 403);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "User identity has to be verified before any payment method can be added."
            ], 403);
        }
    }

    public function readPaymentMethod(Request $request)
    {
        $payment_manager = new PaymentManager();
        $retrieve_payment_method_response = null;
        if ($request->get("action") == "deposit") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                $retrieve_payment_method_response = $payment_manager->manage(array("type" => "retrieve_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["id" => $request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        } else if ($request->get("action") == "withdrawal") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                $retrieve_payment_method_response = $payment_manager->manage(array("type" => "retrieve_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["id" => $request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        }
        if (isset($retrieve_payment_method_response) && isset($retrieve_payment_method_response["id"])) {
            return response()->json([
                "status" => true,
                "message" => "Payment method data retrieved successfully.",
                "data" => $retrieve_payment_method_response
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while retrieving payment method, payment method could not be retrieved."
            ], 500);
        }
    }

    public function readAllPaymentMethod(Request $request)
    {
        $payment_manager = new PaymentManager();
        $list_all_payment_method_response = [];
        if ($request->get("action") == "deposit") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                $list_all_payment_method_response = $payment_manager->manage(array("type" => "list_all_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => $request->all()));
            } else {
                return response()->json([
                    "status" => true,
                    "message" => "All payment method data retrieved successfully.",
                    "data" => $list_all_payment_method_response
                ], 200);
            }
        } else if ($request->get("action") == "withdrawal") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                $list_all_payment_method_response = $payment_manager->manage(array("type" => "list_all_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => $request->all()));
            } else {
                return response()->json([
                    "status" => true,
                    "message" => "All payment method data retrieved successfully.",
                    "data" => $list_all_payment_method_response
                ], 200);
            }
        }
        if (isset($list_all_payment_method_response) && isset($list_all_payment_method_response["data"])) {
            return response()->json([
                "status" => true,
                "message" => "All payment method data retrieved successfully.",
                "data" => $list_all_payment_method_response["data"]
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while retrieving all payment methods, all payment methods could not be retrieved."
            ], 500);
        }
    }

    public function updateDefaultPaymentMethod(Request $request)
    {
        $payment_manager = new PaymentManager();
        $update_default_payment_method_response = null;
        if ($request->request->get("action") == "deposit") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                $update_default_payment_method_response = $payment_manager->manage(array("type" => "update_default_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["id" => $request->request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        } else if ($request->request->get("action") == "withdrawal") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                $update_default_payment_method_response = $payment_manager->manage(array("type" => "update_default_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["id" => $request->request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        }
        if (isset($update_default_payment_method_response) && isset($update_default_payment_method_response["id"])) {
            return response()->json([
                "status" => true,
                "message" => "Default payment method updated successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while updating default payment method, default payment method could not be updated."
            ], 500);
        }
    }

    public function deletePaymentMethod(Request $request)
    {
        $payment_manager = new PaymentManager();
        $delete_payment_method_response = null;
        if ($request->get("action") == "deposit") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id") != "") {
                $delete_payment_method_response = $payment_manager->manage(array("type" => "delete_customer_payment_method", "customer_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_customer_id"), "data" => ["id" => $request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        } else if ($request->get("action") == "withdrawal") {
            if (User::where("user_id", $request->request->get("user_id"))->value("payment_account_id") != "") {
                $delete_payment_method_response = $payment_manager->manage(array("type" => "delete_account_payment_method", "account_id" => User::where("user_id", $request->request->get("user_id"))->value("payment_account_id"), "data" => ["id" => $request->get("id")]));
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "User payment data not found."
                ], 404);
            }
        }
        if (isset($delete_payment_method_response) && isset($delete_payment_method_response["deleted"]) && $delete_payment_method_response["deleted"]) {
            return response()->json([
                "status" => true,
                "message" => "Payment method data deleted successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while deleting payment method, payment method could not be deleted."
            ], 500);
        }
    }
}
