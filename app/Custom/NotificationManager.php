<?php

namespace App\Custom;

use App\Models\Login;
use App\Models\Notification;
use DateTime;

class NotificationManager
{

  function sendNotification($array, $data, $type)
  {
    if ($type == "general") {
      $user_ids = Login::all()->pluck("user_id")->unique();
    } else {
      $user_ids = [$array["receiver_user_id"]];
    }
    if (count($user_ids) > 0) {
      foreach ($user_ids as $user_id) {
        do {
          $array["notification_id"] = uniqid(rand(), true);
        } while (Notification::where("notification_id", $array["notification_id"])->exists());
        $array["receiver_user_id"] = $user_id;

        if (Login::where("user_id", $user_id)->exists()) {
          $app_language_code = Login::where("user_id", $user_id)->latest("updated_at")->first()->app_language_code;
          $localization = new Localization($app_language_code, $data);
        } else {
          $localization = new Localization("", $data);
        }
        $array["title"] = $localization->getText($array["title_key"]);
        $array["body"] = $localization->getText($array["body_key"]);

        $device_tokens = Login::where("user_id", $user_id)->where("device_token", "!=", "")->get()->pluck("device_token");
        if (count($device_tokens) > 0) {
          foreach ($device_tokens as $device_token) {
            $current_period = date('y-m-d h:i:s');
            $updated_at = Login::where("device_token", $device_token)->value("device_token_updated_at");
            $start_datetime = new DateTime($current_period);
            $end_datetime = new DateTime($updated_at);
            $diff = $start_datetime->diff($end_datetime);
            if ($diff->m > 2) {
              Login::where("device_token", $device_token)->delete();
            } else {
              $device_os = Login::where("device_token", $device_token)->value("device_os");
              if ($device_os == "android" || $device_os == "ios") {
                $app_language_code = Login::where("device_token", $device_token)->value("app_language_code");
                $localization = new Localization($app_language_code, $data);
                $title = $localization->getText($array["title_key"]);
                $body = $localization->getText($array["body_key"]);
                $badge = Notification::where("receiver_user_id", $user_id)->where("seen", false)->count() + 1;

                if ($device_os == "android") {
                  $priority = "high";
                  $sound = "notifications.mp3";
                } else {
                  $priority = 10;
                  $sound = "notifications.caf";
                }

                $notification = ["title" => $title, "body" => $body, "sound" => $sound, "icon" => "logo_notification", "android_channel_id" => "megalo_general_channel_id", "badge" => $badge];
                $json = json_encode(["to" => $device_token, "notification" => $notification, "data" => array_merge($data, $notification), "priority" => $priority]);
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
                if (isset($responseData["results"][0]["error"])) {
                  $error_message = $responseData["results"][0]["error"];
                  if ($error_message == "NotRegistered" || $error_message == "InvalidRegistration") {
                    Login::where("device_token", $device_token)->delete();
                  }
                }

                curl_close($curl);
              }
            }
          }
        }
        $notification = Notification::Create($array);
        $notification_data = collect(Notification::find($notification->notification_id));
        $notification_data = $notification_data->map(function ($item) use ($user_id) {
          $item->user_id = $user_id;
          $item->type = "has_unseen_notification";
          $item->status = true;
          return $item;
        });
        $websocket = new WebSocket();
        $websocket->trigger($notification_data);
      }
    }
  }
}
