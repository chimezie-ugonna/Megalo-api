<?php

namespace App\Http\Controllers;

use App\Custom\MediaManager;
use App\Custom\NotificationManager;
use App\Models\Earning;
use App\Models\Investment;
use App\Models\PaidDividend;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function create(Request $request)
    {
        $status = true;
        $request->request->add(["company_percentage" => 100 - $request->request->get("percentage_available")]);
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
            Property::find($property->property_id)->propertyValueHistory()->create(["property_id" => $property->property_id, "value_usd" => $property->value_usd, "value_annual_change_percentage" => $property->value_average_annual_change_percentage]);
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
            if (!Property::where("property_id", $request->request->get("property_id"))->value("sold")) {
                $status = true;
                if (PaidDividend::where("property_id", $request->request->get("property_id"))->exists()) {
                    $last_dividend_payment_period = strtotime(PaidDividend::where("property_id", $request->request->get("property_id"))->latest()->first()->created_at);
                    $last_dividend_payment_year = date("Y", $last_dividend_payment_period);
                    $last_dividend_payment_month = date("m", $last_dividend_payment_period);
                    date_default_timezone_set("UTC");
                    $current_year = date("Y");
                    $current_month = date("m");
                    if ($last_dividend_payment_year == $current_year && $last_dividend_payment_month == $current_month) {
                        $status = false;
                    }

                    return response()->json([
                        "status" => $status,
                        "last_dividend_payment_year" => $last_dividend_payment_year,
                        "current_year" => $current_year,
                        "last_dividend_payment_month" => $last_dividend_payment_month,
                        "current_month" => $current_month
                    ], 200);
                }
                /*if ($status) {
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
                }*/
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "This property has been sold so it can not pay dividends any longer."
                ], 403);
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
            $investment_percentage = ($request->request->get("amount_usd") / $current_property_value) * 100;
            $value_average_annual_change_percentage = Property::where("property_id", $request->request->get("property_id"))->value("value_average_annual_change_percentage");
            $potential_property_value = $current_property_value * (1 + ($value_average_annual_change_percentage / 100)) ** $request->request->get("time_period");
            $potential_investment_value = ($investment_percentage / 100) * $potential_property_value;

            $current_property_monthly_earning = Property::where("property_id", $request->request->get("property_id"))->value("monthly_earning_usd");
            $potential_property_earning = $current_property_monthly_earning * ($request->request->get("time_period") * 12);
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
        return response()->json([
            "status" => true,
            "message" => "All property data retrieved successfully.",
            "data" => Property::latest()->get()
        ], 200);
    }

    public function readPaidDividend(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All paid dividend data retrieved successfully.",
            "data" => PaidDividend::where("property_id", $request->get("property_id"))->latest()->get()
        ], 200);
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
                    if ($request->request->has("value_usd") && $request->filled("value_usd") && $request->request->get("value_usd") != $current_property_value) {
                        $value_annual_change_percentage = (($request->request->get("value_usd") - $current_property_value) / $current_property_value) * 100;
                        Property::find($request->request->get("property_id"))->propertyValueHistory()->create(["property_id" => $request->request->get("property_id"), "value_usd" => $request->request->get("value_usd"), "value_annual_change_percentage" => $value_annual_change_percentage]);
                        $request->request->add(["value_average_annual_change_percentage" => (Property::find($request->request->get("property_id"))->propertyValueHistory()->sum("value_annual_change_percentage")) / Property::find($request->request->get("property_id"))->propertyValueHistory()->count()]);
                    }
                    Property::where("property_id", $request->request->get("property_id"))->update($request->except(["user_id"]));
                    $investor_user_ids = Investment::where("property_id", $request->request->get("property_id"))->get()->pluck("user_id")->unique();
                    $notification_manager = new NotificationManager();
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

                    if ($request->request->has("sold") && $request->filled("sold") && $request->request->get("sold")) {
                        $current_property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                        $investor_user_ids = Investment::where("property_id", $request->request->get("property_id"))->get()->pluck("user_id")->unique();
                        if (count($investor_user_ids) > 0) {
                            foreach ($investor_user_ids as $user_id) {
                                if (User::where("user_id", $user_id)->exists()) {
                                    $user_percentage = Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $user_id)->value("percentage");
                                    $user_percentage_of_property_value = $current_property_value * ($user_percentage / 100);
                                    $user_balance = User::where("user_id", $user_id)->value("balance_usd");
                                    $new_user_balance = $user_balance + $user_percentage_of_property_value;
                                    User::where("user_id", $user_id)->update(["balance_usd" => $new_user_balance]);
                                    Earning::create(["property_id" => $request->request->get("property_id"), "user_id" => $user_id, "amount_usd" => $user_percentage_of_property_value]);
                                    $notification_manager->sendNotification(array(
                                        "receiver_user_id" => $user_id,
                                        "title" => "Property sale payment!!!",
                                        "body" => "You just received $" . number_format($user_percentage_of_property_value, 2) . " in your balance as payment from the sale of a property you invested in.",
                                        "tappable" => true,
                                        "redirection_page" => "earning",
                                        "redirection_page_id" => $request->request->get("property_id")
                                    ), array(), "user_specific");
                                }
                            }
                        }

                        Investment::where("property_id", $request->request->get("property_id"))->delete();
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
                ], 403);
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
                Property::find($request->request->get("property_id"))->propertyValueHistory()->delete();
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
