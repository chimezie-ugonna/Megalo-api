<?php

namespace App\Custom;

use App\Models\Login;
use App\Models\Notification;

class NotificationManager
{

  function sendNotification($array, $data, $type)
  {
    if ($type == "general") {
      $device_tokens = Login::all()->pluck("device_token");
    } else {
      $device_tokens = Login::where("user_id", $array["receiver_user_id"])->where("device_token", "!=", "")->get()->pluck("device_token");
    }
    if (count($device_tokens) > 0) {
      foreach ($device_tokens as $device_token) {
        $device_os = Login::where("device_token", $device_token)->value("device_os");
        if ($device_os == "android" || $device_os == "ios") {
          $user_id = Login::where("device_token", $device_token)->value("user_id");
          if (!array_key_exists("notification_id", $array)) {
            $array["notification_id"] = uniqid(rand(), true);
          }
          if (!array_key_exists("receiver_user_id", $array)) {
            $array["receiver_user_id"] = $user_id;
          }

          $ip_address = Login::where("device_token", $device_token)->value("ip_address");
          if (array_key_exists("title", $array)) {
            unset($array["title"]);
          }
          if (array_key_exists("body", $array)) {
            unset($array["body"]);
          }
          $localization = new Localization($ip_address, $data);
          $array["title"] = $localization->getText($array["title_key"]);
          $array["body"] = $localization->getText($array["body_key"]);

          if ($device_os == "android") {
            $priority = "high";
          } else {
            $priority = "10";
          }

          $notification = ["title" => $array["title"], "body" => $array["body"], "sound" => "notifications.mp3", "icon" => "notification_icon", "android_channel_id" => "megalo_general_channel_id"];
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

          Notification::Create($array);
          unset($array["notification_id"]);
          unset($array["receiver_user_id"]);

          $responseData = json_decode($response, true);
          if (isset($responseData["results"][0]["error"])) {
            $error_message = $responseData["results"][0]["error"];
            if ($error_message == "NotRegistered" || $error_message == "InvalidRegistration") {
              Login::where("device_token", "nbvbbvhbnbnn")->delete();
            }
          }

          curl_close($curl);
          return $responseData;
        }
      }
    }
  }
}
