<?php

namespace App\Http\Controllers;

use App\Custom\NotificationManager;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function create(Request $request)
    {
        $status = true;
        if ($request->request->has("sender_user_id") && $request->filled("sender_user_id")) {
            if (!User::where("user_id", $request->request->get("sender_user_id"))->exists()) {
                $status = false;
            }
        }
        if ($status) {
            if (!User::where("user_id", $request->request->get("receiver_user_id"))->exists()) {
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
        } else {
            return response()->json([
                "status" => false,
                "message" => "Sender not found."
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
        if (sizeof(Notification::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All notification data retrieved successfully.",
                "data" => Notification::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No notification data found."
            ], 404);
        }
    }

    public function readUserSpecific(Request $request)
    {
        if (Notification::where("receiver_user_id", $request->request->get("user_id"))->exists()) {
            return response()->json([
                "status" => true,
                "message" => "Notification data retrieved successfully.",
                "data" => Notification::where("receiver_user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Notification data not found."
            ], 404);
        }
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
