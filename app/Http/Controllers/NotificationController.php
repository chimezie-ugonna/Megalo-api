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
        /*$notification_manager = new NotificationManager();
        $notification_manager->sendNotification($request->all(), array(), "general");
        return response()->json([
            "status" => true,
            "message" => "Notification sent successfully."
        ], 201);*/

        $notification = ["title" => "Test", "body" => "Test", "sound" => "notifications.caf", "icon" => "logo_notification", "android_channel_id" => "megalo_general_channel_id"];
        $json = json_encode([
            "to" => "fwRxtjop_kWLunpEz7THJN:APA91bFd_B6BHcJKyJhRStkYtGBwlm5ToqYGf7XzRKYwnna0Rr55RMOcIqA2Nvf6l8awCYb5TfADHCwDthnGE8R4Q-9ytnDjJfqDxcBfRLDK86M0s45TiCx-KENjj-BPpcFRzoovkbP1", "notification" => $notification, "data" => array_merge([], $notification), "priority" => "10"
        ]);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: key=" . getenv("FCM_SERVER_KEY")
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_CUSTOMREQUEST => "POST"
        ));

        $response = curl_exec($curl);

        $responseData = json_decode($response, true);
        return response()->json([
            "status" => true,
            "message" => $responseData
        ], 200);
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

    public function readAllKey()
    {
        return response()->json([
            "status" => true,
            "message" => "Data retrieved successfully.",
            "data" => [
                ["title_key" => "appreciation_title", "body_key" => "appreciation_body", "description" => "This is a notification to appreciate the recipient(s) for using Megalo."]
            ]
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
