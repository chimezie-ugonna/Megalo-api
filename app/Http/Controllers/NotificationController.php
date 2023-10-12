<?php

namespace App\Http\Controllers;

use App\Custom\NotificationManager;
use App\Custom\WebSocket;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function create(Request $request)
    {
        if (User::where("user_id", $request->request->get("receiver_user_id"))->exists()) {
            $notification_manager = new NotificationManager();
            $notification_manager->sendNotification($request->all(), array(), "user_specific");
            return response()->json([
                "status" => true,
                "message" => "Notification sent successfully."
            ], 201);
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

    public function readAll(Request $request)
    {
        return response()->json([
            "status" => true,
            "message" => "All notification data retrieved successfully.",
            "data" => Notification::latest()->simplePaginate($request->get("limit"))
        ], 200);
    }

    public function readUserSpecific(Request $request)
    {
        $data = collect(Notification::where("receiver_user_id", $request->request->get("user_id"))->latest()->simplePaginate($request->get("limit")));
        return response()->json([
            "status" => true,
            "message" => "Notification data retrieved successfully.",
            "data" => $data
        ], 200);
    }

    public function readAllKey()
    {
        return response()->json([
            "status" => true,
            "message" => "Data retrieved successfully.",
            "data" => [
                ["title_key" => "appreciation_title", "body_key" => "appreciation_body", "description" => "This is a notification to appreciate the recipient(s) for using Megalo."],
                ["title_key" => "test_title", "body_key" => "test_body", "description" => "This is a test notification."]
            ]
        ], 200);
    }

    public function update(Request $request)
    {
        if ($request->request->has("seen_all") && $request->filled("seen_all") && $request->request->get("seen_all")) {
            if (Notification::where("receiver_user_id", $request->request->get("user_id"))->where("seen", false)->exists()) {
                Notification::where("receiver_user_id", $request->request->get("user_id"))->update(["seen" => true]);
                $websocket = new WebSocket();
                $websocket->trigger(["user_id" => $request->request->get("user_id"), "type" => "has_unseen_notification", "status" => false]);
            }
            return response()->json([
                "status" => true,
                "message" => "Notification data updated successfully.",
            ], 200);
        } else {
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
    }

    public function delete(Request $request)
    {
        Notification::destroy($request->get("notification_id"));
        return response()->json([
            "status" => true,
            "message" => "Notification data deleted successfully."
        ], 200);
    }
}
