<?php

namespace App\Http\Controllers;

use App\Custom\MediaManager;
use App\Custom\NotificationManager;
use App\Models\Earning;
use App\Models\Investment;
use App\Models\PaidDividend;
use App\Models\Property;
use App\Models\PropertyHistory;
use App\Models\User;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function create(Request $request)
    {
        $status = true;
        if ($request->request->has("image_urls") && $request->filled("image_urls")) {
            $image_urls = explode(", ", $request->request->get("image_urls"));
            if (count($image_urls) > 0) {
                $cloudinary_image_urls = "";
                $media_manager = new MediaManager();
                for ($i = 0; $i < count($image_urls); $i++) {
                    $data = $media_manager->uploadMedia("image", $image_urls[$i]);
                    if (isset($data) && isset($data["url"]) && isset($data["public_id"])) {
                        if ($i == count($image_urls) - 1) {
                            $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"];
                        } else {
                            $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"] . ", ";
                        }
                    } else {
                        $status = false;
                        break;
                    }
                }
                $request->request->set("image_urls", $cloudinary_image_urls);
            }
        }

        if ($status) {
            $property = Property::Create($request->all());
            Property::find($property->property_id)->propertyHistory()->create(["property_id" => $property->property_id, "value_usd" => $property->value_usd, "monthly_earning_usd" => $property->monthly_earning_usd, "value_annual_change_rate" => 0, "monthly_earning_annual_change_rate" => 0, "value_changed" => false, "monthly_earning_changed" => false]);
            $notification_manager = new NotificationManager();
            $notification_manager->sendNotification(array(
                "title" => "New property available!!!",
                "body" => "We just listed a new property, be among the first to invest in it and reap the benefits.",
                "tappable" => true,
                "redirection_page" => "property",
                "redirection_page_id" => $property->property_id
            ), array(), "general");
            return response()->json([
                "status" => true,
                "message" => "Property added successfully."
            ], 201);
        } else {
            return response()->json([
                "status" => false,
                "message" => "An error occurred while adding property image, property could not be created."
            ], 500);
        }
    }

    public function payDividend(Request $request)
    {
        if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
            $status = true;
            if (PaidDividend::where("property_id", $request->request->get("property_id"))->exists()) {
                $last_dividend_payment_period = strtotime(PaidDividend::where("property_id", $request->request->get("property_id"))->latest()->first()->value("created_at"));
                $last_dividend_payment_year = date("Y", $last_dividend_payment_period);
                $last_dividend_payment_month = date("m", $last_dividend_payment_period);
                date_default_timezone_set("UTC");
                $current_year = date("Y");
                $current_month = date("m");
                if ($last_dividend_payment_year == $current_year && $last_dividend_payment_month == $current_month) {
                    $status = false;
                }
            }
            if ($status) {
                $notification_manager = new NotificationManager();
                $current_property_monthly_earning = Property::where("property_id", $request->request->get("property_id"))->value("monthly_earning_usd");
                $request->request->add(["amount_usd" => $current_property_monthly_earning]);
                $investor_user_ids = Investment::where("property_id", $request->request->get("property_id"))->get()->pluck("user_id")->unique();
                $request->request->add(["investor_count" => count($investor_user_ids)]);
                if (count($investor_user_ids) > 0) {
                    $count = 0;
                    foreach ($investor_user_ids as $user_id) {
                        if (User::where("user_id", $user_id)->exists()) {
                            $user_percentage = Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $user_id)->value("percentage");
                            $user_percentage_of_property_monthly_earning = $current_property_monthly_earning * ($user_percentage / 100);
                            $user_balance = User::where("user_id", $user_id)->value("balance_usd");
                            $new_user_balance = $user_balance + $user_percentage_of_property_monthly_earning;
                            User::where("user_id", $user_id)->update(["balance_usd" => $new_user_balance]);
                            Earning::create(["property_id" => $request->request->get("property_id"), "user_id" => $user_id, "amount_usd" => $user_percentage_of_property_monthly_earning]);
                            $notification_manager->sendNotification(array(
                                "receiver_user_id" => $user_id,
                                "title" => "Property dividend payment!!!",
                                "body" => "You just received $" . number_format($user_percentage_of_property_monthly_earning, 2) . " in your balance as dividend from a property you invested in.",
                                "tappable" => true,
                                "redirection_page" => "earning",
                                "redirection_page_id" => $request->request->get("property_id")
                            ), array(), "user_specific");
                            $count++;
                        }
                    }
                    if ($count > 0) {
                        PaidDividend::Create($request->all());
                        return response()->json([
                            "status" => true,
                            "message" => "Property dividends paid successfully."
                        ], 200);
                    } else {
                        return response()->json([
                            "status" => false,
                            "message" => "This property does not have any investor to pay."
                        ], 404);
                    }
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "This property does not have any investor to pay."
                    ], 404);
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "This property has already paid its dividend this month."
                ], 400);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function calculatePotential(Request $request)
    {
        if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
            $current_property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
            $current_value_annual_change_rate = Property::where("property_id", $request->request->get("property_id"))->value("current_value_annual_change_rate");
            $investment_percentage = ($request->request->get("amount_usd") / $current_property_value) * 100;
            $potential_property_value = $current_property_value * (1 + $current_value_annual_change_rate) ** $request->request->get("time_period");
            $potential_investment_value = ($investment_percentage / 100) * $potential_property_value;

            $current_property_monthly_earning = Property::where("property_id", $request->request->get("property_id"))->value("monthly_earning_usd");
            $current_monthly_earning_annual_change_rate = Property::where("property_id", $request->request->get("property_id"))->value("current_monthly_earning_annual_change_rate");
            $potential_property_earning = ($current_property_monthly_earning * 12) * (1 + $current_monthly_earning_annual_change_rate) ** $request->request->get("time_period");
            $potential_earning = ($investment_percentage / 100) * $potential_property_earning;
            return response()->json([
                "status" => true,
                "message" => "Potential calculated successfully.",
                "data" => ["potential_investment_value" => $potential_investment_value, "potential_earning" => $potential_earning]
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function read(Request $request)
    {
        if (Property::where("property_id", $request->get("property_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Property data retrieved successfully.",
                "data" => Property::where("property_id", $request->get("property_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        if (sizeof(Property::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All property data retrieved successfully.",
                "data" => Property::latest()->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No property data found."
            ], 404);
        }
    }

    public function readPaidDividend(Request $request)
    {
        if (PaidDividend::where("property_id", $request->get("property_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "All paid dividend data retrieved successfully.",
                "data" => PaidDividend::where("property_id", $request->get("property_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No paid dividend data found."
            ], 404);
        }
    }

    public function update(Request $request)
    {
        if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
            if (!Property::where("property_id", $request->request->get("property_id"))->value("sold")) {
                $status = true;
                if ($request->request->has("image_urls") && $request->filled("image_urls")) {
                    $current_image_urls_with_public_id = explode(", ", Property::where("property_id", $request->request->get("property_id"))->value("image_urls"));
                    $new_image_urls = explode(", ", $request->request->get("image_urls"));
                    $cloudinary_image_urls = "";
                    $current_image_urls = array();
                    $media_manager = new MediaManager();

                    for ($i = 0; $i < count($current_image_urls_with_public_id); $i++) {
                        $data = explode("+ ", $current_image_urls_with_public_id[$i]);
                        if (count($data) > 1) {
                            if (!in_array($data[0], $new_image_urls)) {
                                $response = $media_manager->deleteMedia("image", $data[1]);
                                if (!isset($response) || !isset($response["result"]) || $response["result"] != "ok") {
                                    $status = false;
                                    break;
                                }
                            } else {
                                $cloudinary_image_urls .= $current_image_urls_with_public_id[$i] . ", ";
                            }
                            $current_image_urls[$i] = $data[0];
                        }
                    }

                    if ($status) {
                        for ($i = 0; $i < count($new_image_urls); $i++) {
                            if (!in_array($new_image_urls[$i], $current_image_urls)) {
                                $data = $media_manager->uploadMedia("image", $new_image_urls[$i]);
                                if (isset($data) && isset($data["url"]) && isset($data["public_id"])) {
                                    $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"] . ", ";
                                } else {
                                    $status = false;
                                    break;
                                }
                            }
                        }
                        $request->request->set("image_urls", substr($cloudinary_image_urls, 0, strlen($cloudinary_image_urls) - 2));
                    }
                }

                if ($status) {
                    $current_property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                    $current_property_monthly_earnings = Property::where("property_id", $request->request->get("property_id"))->value("monthly_earning_usd");
                    if ($request->request->has("value_usd") && $request->filled("value_usd") || $request->request->has("monthly_earning_usd") && $request->filled("monthly_earning_usd")) {
                        $current_value_annual_change_rate = 0;
                        $current_monthly_earning_annual_change_rate = 0;
                        if ($request->request->has("value_usd") && $request->filled("value_usd")) {
                            $current_value_annual_change_rate = ($request->request->get("value_usd") - $current_property_value) / $current_property_value;
                        }
                        if ($request->request->has("monthly_earning_usd") && $request->filled("monthly_earning_usd")) {
                            $current_monthly_earning_annual_change_rate = ($request->request->get("monthly_earning_usd") - $current_property_monthly_earnings) / $current_property_monthly_earnings;
                        }

                        $request->request->add(["current_value_annual_change_rate" => $current_value_annual_change_rate]);
                        $request->request->add(["current_monthly_earning_annual_change_rate" => $current_monthly_earning_annual_change_rate]);
                    }
                    Property::where("property_id", $request->request->get("property_id"))->update($request->except(["user_id"]));
                    $investor_user_ids = Investment::where("property_id", $request->request->get("property_id"))->get()->pluck("user_id")->unique();
                    $notification_manager = new NotificationManager();
                    if ($request->request->has("value_usd") && $request->filled("value_usd") || $request->request->has("monthly_earning_usd") && $request->filled("monthly_earning_usd")) {
                        $value_usd = $current_property_value;
                        $value_changed = false;
                        $monthly_earning_usd = $current_property_monthly_earnings;
                        $monthly_earning_changed = false;

                        if ($request->request->has("value_usd") && $request->filled("value_usd") && $request->request->get("value_usd") != $current_property_value) {
                            $value_usd = $request->request->get("value_usd");
                            $value_changed = true;
                        }
                        if ($request->request->has("monthly_earning_usd") && $request->filled("monthly_earning_usd") && $request->request->get("monthly_earning_usd") != $current_property_monthly_earnings) {
                            $monthly_earning_usd = $request->request->get("monthly_earning_usd");
                            $monthly_earning_changed = true;
                        }
                        if ($value_changed || $monthly_earning_changed) {
                            PropertyHistory::create(["property_id" => $request->request->get("property_id"), "value_usd" => $value_usd, "monthly_earning_usd" => $monthly_earning_usd, "value_annual_change_rate" => $current_value_annual_change_rate, "monthly_earning_annual_change_rate" => $current_monthly_earning_annual_change_rate, "value_changed" => $value_changed, "monthly_earning_changed" => $monthly_earning_changed]);
                        }

                        if ($request->request->has("value_usd") && $request->filled("value_usd") && $request->request->get("value_usd") > $current_property_value) {
                            if (count($investor_user_ids) > 0) {
                                foreach ($investor_user_ids as $user_id) {
                                    if (User::where("user_id", $user_id)->exists()) {
                                        $notification_manager->sendNotification(array(
                                            "receiver_user_id" => $user_id,
                                            "title" => "Property value increase!!!",
                                            "body" => "A property that you invested in has increased in value.",
                                            "tappable" => true,
                                            "redirection_page" => "property",
                                            "redirection_page_id" => $request->request->get("property_id")
                                        ), array(), "user_specific");
                                    }
                                }
                            }
                        }

                        if ($request->request->has("monthly_earning_usd") && $request->filled("monthly_earning_usd") && $request->request->get("monthly_earning_usd") > $current_property_monthly_earnings) {
                            if (count($investor_user_ids) > 0) {
                                foreach ($investor_user_ids as $user_id) {
                                    if (User::where("user_id", $user_id)->exists()) {
                                        $notification_manager->sendNotification(array(
                                            "receiver_user_id" => $user_id,
                                            "title" => "Property earnings increase!!!",
                                            "body" => "A property that you invested in has increased its earnings.",
                                            "tappable" => true,
                                            "redirection_page" => "property",
                                            "redirection_page_id" => $request->request->get("property_id")
                                        ), array(), "user_specific");
                                    }
                                }
                            }
                        }
                    }

                    return response()->json([
                        "status" => true,
                        "message" => "Property data updated successfully.",
                        "data" => Property::where("property_id", $request->request->get("property_id"))->get()
                    ], 200);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "An error occurred while updating property image, property data could not be updated."
                    ], 500);
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "This property has been sold so its details can not be edited any longer."
                ], 404);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        if (Property::where("property_id", $request->request->get("property_id"))->exists()) {
            $status = true;
            $image_urls = explode(", ", Property::where("property_id", $request->request->get("property_id"))->value("image_urls"));
            if (count($image_urls) > 0) {
                $media_manager = new MediaManager();
                for ($i = 0; $i < count($image_urls); $i++) {
                    $data = explode("+ ", $image_urls[$i]);
                    if (count($data) > 1) {
                        $data = $media_manager->deleteMedia("image", $data[1]);
                        if (!isset($data) || !isset($data["result"]) || $data["result"] != "ok") {
                            $status = false;
                            break;
                        }
                    }
                }
            }

            if ($status) {
                Property::find($request->request->get("property_id"))->investment()->delete();
                Property::find($request->request->get("property_id"))->paidDividend()->delete();
                Property::find($request->request->get("property_id"))->earning()->delete();
                Property::find($request->request->get("property_id"))->propertyHistory()->delete();
                Property::destroy($request->request->get("property_id"));
                return response()->json([
                    "status" => true,
                    "message" => "Property data deleted successfully.",
                ], 200);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "An error occurred while deleting property image, property data could not be deleted."
                ], 500);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }
}
