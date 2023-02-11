<?php

namespace App\Custom;

use App\Models\Login;
use App\Models\Notification as ModelsNotification;
use GuzzleHttp\Client as GuzzleHttpClient;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;

class NotificationManager
{

  private $client;

  function __construct()
  {
    $this->client = new Client();
    $this->client->setApiKey(getenv("FCM_SERVER_KEY"));
    $this->client->injectGuzzleHttpClient(new GuzzleHttpClient());
  }

  function sendNotification($array, $data, $type)
  {
    $response = true;
    if ($type == "general") {
      $device_tokens = Login::all()->pluck("device_token");
    } else {
      $device_tokens = Login::where("user_id", $array["receiver_user_id"])->get()->pluck("device_token");
    }
    if (count($device_tokens) > 0) {
      foreach ($device_tokens as $device_token) {
        $device_os = Login::where("device_token", $device_token)->value("device_os");
        if ($device_token != "" && $device_os == "android" || $device_token != "" && $device_os == "ios") {
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

          $message = new Message();
          $notification = new Notification($array["title"], $array["body"]);
          //$notification->setSound("notifications");
          //$notification->setIcon("notification_icon");
          $message->setNotification($notification);
          $message->setData($data);
          if ($device_os == "android") {
            $message->setPriority("high");
          } else if ($device_os == "ios") {
            $message->setPriority("10");
          }
          $message->addRecipient(new Device($device_token));
          $response = $this->client->send($message);

          ModelsNotification::Create($array);
          unset($array["notification_id"]);
          unset($array["receiver_user_id"]);
        }
      }
    }

    /*$responseData = $response->json();

    foreach ($responseData["results"] as $i => $result) {
      if (isset($result["error"])) {
        deleteUserFcmToken($recipients[$i]);
      }
    }*/

    return $response;
  }
}
