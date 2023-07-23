<?php

namespace App\Http\Controllers;

use App\Custom\MediaManager;
use App\Custom\NotificationManager;
use App\Custom\WebSocket;
use App\Models\Earning;
use App\Models\Investment;
use App\Models\PaidDividend;
use App\Models\Property;
use App\Models\PropertyValueHistory;
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
                    $data = $media_manager->uploadMedia("image", $image_urls[$i], "properties");
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
            $websocket = new WebSocket();
            $request->request->add(["type" => "new_property"]);
            $websocket->trigger($request->all());
            $notification_manager = new NotificationManager();
            $notification_manager->sendNotification(array(
                "title_key" => "new_property_available_title",
                "body_key" => "new_property_available_body",
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
                    $investor_count = count($investor_user_ids);
                    $company_percentage = Property::where("property_id", $request->request->get("property_id"))->value("company_percentage");
                    if ($company_percentage > 0) {
                        $investor_count += 1;
                    }
                    $request->request->add(["investor_count" => $investor_count]);
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
                                if (number_format($user_percentage_of_property_monthly_earning, 2) < 0.01) {
                                    $body_key = "property_dividend_payment_body_2";
                                } else {
                                    $body_key = "property_dividend_payment_body";
                                }
                                $notification_manager->sendNotification(array(
                                    "receiver_user_id" => $user_id,
                                    "title_key" => "property_dividend_payment_title",
                                    "body_key" => $body_key,
                                    "tappable" => true,
                                    "redirection_page" => "earning",
                                    "redirection_page_id" => $request->request->get("property_id")
                                ), array("user_percentage_of_property_monthly_earning" => number_format($user_percentage_of_property_monthly_earning, 2)), "user_specific");
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
                    ], 403);
                }
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
            if (!Property::where("property_id", $request->request->get("property_id"))->value("sold")) {
                if (Property::where("property_id", $request->request->get("property_id"))->value("percentage_available") != 0.0) {
                    $current_property_value = Property::where("property_id", $request->request->get("property_id"))->value("value_usd");
                    $investment_percentage = ($request->request->get("amount_usd") / $current_property_value) * 100;
                    $value_average_annual_change_percentage = Property::where("property_id", $request->request->get("property_id"))->value("value_average_annual_change_percentage");
                    $current_property_monthly_earning = Property::where("property_id", $request->request->get("property_id"))->value("monthly_earning_usd");

                    $data = array();
                    if ($request->request->has("time_period") && $request->filled("time_period")) {
                        $potential_property_value = $current_property_value * (1 + ($value_average_annual_change_percentage / 100)) ** $request->request->get("time_period");
                        $potential_investment_value = ($investment_percentage / 100) * $potential_property_value;

                        $potential_property_earning = $current_property_monthly_earning * ($request->request->get("time_period") * 12);
                        $potential_earning = ($investment_percentage / 100) * $potential_property_earning;

                        $data["potential_investment_value"] = $potential_investment_value;
                        $data["potential_earning"] = $potential_earning;
                    } else {
                        for ($i = 0; $i < 10; $i++) {
                            $potential_property_value = $current_property_value * (1 + ($value_average_annual_change_percentage / 100)) ** ($i + 1);
                            $potential_investment_value = ($investment_percentage / 100) * $potential_property_value;

                            $potential_property_earning = $current_property_monthly_earning * (($i + 1) * 12);
                            $potential_earning = ($investment_percentage / 100) * $potential_property_earning;

                            $data[$i] = ["potential_investment_value" => $potential_investment_value, "potential_earning" => $potential_earning];
                        }
                    }
                    return response()->json([
                        "status" => true,
                        "message" => "Potential calculated successfully.",
                        "data" => $data
                    ], 200);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "This property is no longer available for investment so no potential can be calculated."
                    ], 403);
                }
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "This property has been sold so no potential can be calculated."
                ], 403);
            }
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
            if (!Property::where("property_id", $request->request->get("property_id"))->value("sold")) {
                return response()->json([
                    "status" => true,
                    "message" => "Property data retrieved successfully.",
                    "data" => Property::where("property_id", $request->get("property_id"))->get()
                ], 200);
            } else {
                if (User::where("user_id", $request->request->get("user_id"))->value("is_admin") && $request->header("access-type") != "mobile") {
                    return response()->json([
                        "status" => true,
                        "message" => "Property data retrieved successfully.",
                        "data" => Property::where("property_id", $request->get("property_id"))->get()
                    ], 200);
                } else {
                    return response()->json([
                        "status" => false,
                        "message" => "This property has been sold and its details can no longer be read without authorized access."
                    ], 403);
                }
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function readAll(Request $request)
    {
        if (User::where("user_id", $request->request->get("user_id"))->value("is_admin") && $request->header("access-type") != "mobile") {
            return response()->json([
                "status" => true,
                "message" => "All property data retrieved successfully.",
                "data" => Property::latest()->simplePaginate($request->get("limit"))
            ], 200);
        } else {
            return response()->json([
                "status" => true,
                "message" => "All property data retrieved successfully.",
                "data" => Property::where("sold", false)->latest()->simplePaginate($request->get("limit"))
            ], 200);
        }
    }

    public function readPaidDividend(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All paid dividend data retrieved successfully.",
            "data" => PaidDividend::where("property_id", $request->get("property_id"))->latest()->simplePaginate($request->get("limit"))
        ], 200);
    }

    public function readPropertyValueHistory(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All property value data retrieved successfully.",
            "data" => PropertyValueHistory::where("property_id", $request->get("property_id"))->latest()->simplePaginate($request->get("limit"))
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
                                $data = $media_manager->uploadMedia("image", $new_image_urls[$i], "properties");
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
                    if ($request->request->has("sold") && $request->filled("sold") && $request->request->get("sold")) {
                        $request->request->add(["value_usd" => 0, "percentage_available" => 0, "monthly_earning_usd" => 0, "value_average_annual_change_percentage" => 0, "company_percentage" => 0]);
                    }
                    Property::where("property_id", $request->request->get("property_id"))->update($request->except(["user_id"]));
                    $websocket = new WebSocket();
                    $request->request->add(["type" => "update_property"]);
                    $websocket->trigger($request->all());
                    $investor_user_ids = Investment::where("property_id", $request->request->get("property_id"))->get()->pluck("user_id")->unique();
                    $notification_manager = new NotificationManager();
                    if ($request->request->has("value_usd") && $request->filled("value_usd") && $request->request->get("value_usd") > $current_property_value) {
                        if (count($investor_user_ids) > 0) {
                            foreach ($investor_user_ids as $user_id) {
                                if (User::where("user_id", $user_id)->exists()) {
                                    $notification_manager->sendNotification(array(
                                        "receiver_user_id" => $user_id,
                                        "title_key" => "property_value_increase_title",
                                        "body_key" => "property_value_increase_body",
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
                                        "title_key" => "property_earnings_increase_title",
                                        "body_key" => "property_earnings_increase_body",
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
                                    $user_amount_invested = Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $user_id)->value("amount_invested_usd");
                                    $user_percentage = Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $user_id)->value("percentage");
                                    $user_percentage_of_property_value = $current_property_value * ($user_percentage / 100);
                                    $user_balance = User::where("user_id", $user_id)->value("balance_usd");
                                    $new_user_balance = $user_balance + $user_percentage_of_property_value;
                                    User::where("user_id", $user_id)->update(["balance_usd" => $new_user_balance]);
                                    $earning = $user_percentage_of_property_value - $user_amount_invested;
                                    if ($earning > 0) {
                                        Earning::create(["property_id" => $request->request->get("property_id"), "user_id" => $user_id, "amount_usd" => $earning]);
                                    }
                                    if (number_format($user_percentage_of_property_value, 2) < 0.01) {
                                        $body_key = "property_sale_payment_body_2";
                                    } else {
                                        $body_key = "property_sale_payment_body";
                                    }
                                    $notification_manager->sendNotification(array(
                                        "receiver_user_id" => $user_id,
                                        "title_key" => "property_sale_payment_title",
                                        "body_key" => $body_key,
                                        "tappable" => true,
                                        "redirection_page" => "earning",
                                        "redirection_page_id" => $request->request->get("property_id")
                                    ), array("user_percentage_of_property_value" => number_format($user_percentage_of_property_value, 2)), "user_specific");
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
                    "message" => "This property has been sold so its details can not be updated any longer."
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
        if (Property::where("property_id", $request->get("property_id"))->exists()) {
            $status = true;
            $image_urls = explode(", ", Property::where("property_id", $request->get("property_id"))->value("image_urls"));
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
                Property::find($request->get("property_id"))->investment()->delete();
                Property::find($request->get("property_id"))->paidDividend()->delete();
                Property::find($request->get("property_id"))->earning()->delete();
                Property::find($request->get("property_id"))->propertyValueHistory()->delete();
                Property::destroy($request->get("property_id"));
                $websocket = new WebSocket();
                $request->request->add(["type" => "delete_property"]);
                $websocket->trigger($request->all());
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
