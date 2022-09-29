<?php

namespace App\Http\Controllers;

use App\Custom\MediaManager;
use App\Custom\NotificationManager;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function create(Request $request)
    {
        $status = "good";
        if ($request->request->has("image_urls") && $request->filled("image_urls")) {
            $image_urls = explode(", ", $request->request->get("image_urls"));
            if (count($image_urls) > 0) {
                $cloudinary_image_urls = "";
                $media_manager = new MediaManager();
                for ($i = 0; $i < count($image_urls); $i++) {
                    $data = $media_manager->uploadMedia("image", $image_urls[$i]);
                    if ($data != false && isset($data["url"]) && isset($data["public_id"])) {
                        if ($i == count($image_urls) - 1) {
                            $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"];
                        } else {
                            $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"] . ", ";
                        }
                    } else {
                        $status = "bad";
                        break;
                    }
                }
                $request->request->set("image_urls", $cloudinary_image_urls);
            }
        }
        if ($status == "good") {
            Property::firstOrCreate(["property_id" => $request->request->get("property_id")], $request->all());
            $notification_manager = new NotificationManager();
            $notification_manager->sendNotification(array("title" => "New property available!!!", "body" => "We just listed a new property, be the first to invest in it and reap the benefits."), array(), "normal", "general");
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

    public function read(Request $request)
    {
        if (Property::find($request->get("property_id"))) {
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
                "data" => Property::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No property data found."
            ], 404);
        }
    }

    public function update(Request $request)
    {
        if (Property::find($request->request->get("property_id"))) {
            $status = "good";
            if ($request->request->has("image_urls") && $request->filled("image_urls")) {
                $image_urls = explode(", ", Property::where("property_id", $request->request->get("property_id"))->value("image_urls"));
                if (count($image_urls) > 0) {
                    $media_manager = new MediaManager();
                    for ($i = 0; $i < count($image_urls); $i++) {
                        $data = explode("+ ", $image_urls[$i]);
                        if (count($data) > 1) {
                            $data = $media_manager->deleteMedia("image", $data[1]);
                            if ($data == false || !isset($data["result"]) || $data["result"] != "ok") {
                                $status = "bad";
                                break;
                            }
                        }
                    }
                }

                if ($status == "good") {
                    $image_urls = explode(", ", $request->request->get("image_urls"));
                    if (count($image_urls) > 0) {
                        $cloudinary_image_urls = "";
                        $media_manager = new MediaManager();
                        for ($i = 0; $i < count($image_urls); $i++) {
                            $data = $media_manager->uploadMedia("image", $image_urls[$i]);
                            if ($data != false && isset($data["url"]) && isset($data["public_id"])) {
                                if ($i == count($image_urls) - 1) {
                                    $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"];
                                } else {
                                    $cloudinary_image_urls .= $data["url"] . "+ " . $data["public_id"] . ", ";
                                }
                            } else {
                                $status = "bad";
                                break;
                            }
                        }
                        $request->request->set("image_urls", $cloudinary_image_urls);
                    }
                }
            }
            if ($status == "good") {
                Property::find($request->request->get("property_id"))->update($request->all());
                return response()->json([
                    "status" => true,
                    "message" => "Property data updated successfully.",
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
                "message" => "Property data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        if (Property::find($request->request->get("property_id"))) {
            $status = "good";
            $image_urls = explode(", ", Property::where("property_id", $request->request->get("property_id"))->value("image_urls"));
            if (count($image_urls) > 0) {
                $media_manager = new MediaManager();
                for ($i = 0; $i < count($image_urls); $i++) {
                    $data = explode("+ ", $image_urls[$i]);
                    if (count($data) > 1) {
                        $data = $media_manager->deleteMedia("image", $data[1]);
                        if ($data == false || !isset($data["result"]) || $data["result"] != "ok") {
                            $status = "bad";
                            break;
                        }
                    }
                }
            }

            if ($status == "good") {
                Property::find($request->request->get("property_id"))->investment()->delete();
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
