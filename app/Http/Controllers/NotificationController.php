<?php

namespace App\Http\Controllers;

use App\Custom\NotificationManager;
use App\Models\Login;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function create(Request $request)
    {
        if (User::where("user_id", $request->request->get("receiver_user_id"))->exists()) {
            $notification_manager = new NotificationManager();
            $responseData = $notification_manager->sendNotification($request->all(), array(), "user_specific");
            if (isset($responseData["results"][0]["error"])) {
                $error_message = $responseData["results"][0]["error"];
                if ($error_message == "NotRegistered" || $error_message == "InvalidRegistration") {
                    Login::where("device_token", "cyAaHN34RfCg5_F6xOwaut:APA91bF2RWkBntRWgB-ahcvPYIyPJGoFcnV5stW8lrI8qi2wriXSuQulaEohB0G7utlK_RlSAI03ci7NUbZmSog6bH_P3YdvujBglE1esqGAdLmweGtZblzuAGuWhAHhGgD3pgQoHyft")->delete();
                    return response()->json([
                        "status" => false,
                        "message" => "Notification failed.",
                        "response" => $responseData
                    ], 500);
                }
            } else {
                return response()->json([
                    "status" => true,
                    "message" => "Notification sent successfully.",
                    "response" => $responseData
                ], 201);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Receiver not found."
            ], 404);
        }
    }

    public function createAll(Request $request)
    {
        $notification_manager = new NotificationManager();
        $notification_manager->sendNotification($request->all(), array(), "general");
        return response()->json([
            "status" => true,
            "message" => "Notification sent successfully."
        ], 201);
    }

    public function read(Request $request)
    {
        if (Notification::where("notification_id", $request->get("notification_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Notification data retrieved successfully.",
                "data" => Notification::where("notification_id", $request->get("notification_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Notification data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        return response()->json([
            "status" => true,
            "message" => "All notification data retrieved successfully.",
            "data" => Notification::latest()->get()
        ], 200);
    }

    public function readUserSpecific(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "Notification data retrieved successfully.",
            "data" => Notification::where("receiver_user_id", $request->request->get("user_id"))->latest()->get()
        ], 200);
    }

    public function update(Request $request)
    {
        if (Notification::where("notification_id", $request->request->get("notification_id"))->exists()) {
            Notification::where("notification_id", $request->request->get("notification_id"))->update($request->except(["user_id"]));
            return response()->json([
                "status" => true,
                "message" => "Notification data updated successfully.",
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Notification data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        if (Notification::where("notification_id", $request->request->get("notification_id"))->exists()) {
            Notification::destroy($request->request->get("notification_id"));
            return response()->json([
                "status" => true,
                "message" => "Notification data deleted successfully."
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Notification data not found."
            ], 404);
        }
    }
}
