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
      $message = new Message();
      $message->setNotification(new Notification($array["title"], $array["body"]));
      $message->setData($data);
      $message->setPriority("10");
      $ios_device_tokens = Login::where("device_os", "ios")->get()->pluck("device_token");
      $count = 0;

      if (count($ios_device_tokens) > 0) {
        foreach ($ios_device_tokens as $device_token) {
          if ($device_token != "") {
            $message->addRecipient(new Device($device_token));
            $count++;
          }
        }
      }

      if ($count != 0) {
        $response = $this->client->send($message);
      }

      if (isset($response)) {
        $message = new Message();
        $message->setNotification(new Notification($array["title"], $array["body"]));
        $message->setData($data);
        $message->setPriority("high");
        $android_device_tokens = Login::where("device_os", "android")->get()->pluck("device_token");
        $count = 0;

        if (count($android_device_tokens) > 0) {
          foreach ($android_device_tokens as $device_token) {
            if ($device_token != "") {
              $message->addRecipient(new Device($device_token));
              $count++;
            }
          }
        }

        if ($count != 0) {
          $response = $this->client->send($message);
        }
      }
    } else {
      $message = new Message();
      $message->setNotification(new Notification($array["title"], $array["body"]));
      $message->setData($data);
      $message->setPriority("10");
      $ios_device_tokens = Login::where("user_id", $array["receiver_user_id"])->where("device_os", "ios")->get()->pluck("device_token");
      $count = 0;

      if (count($ios_device_tokens) > 0) {
        foreach ($ios_device_tokens as $device_token) {
          if ($device_token != "") {
            $message->addRecipient(new Device($device_token));
            $count++;
          }
        }
      }

      if ($count != 0) {
        $response = $this->client->send($message);
      }

      if (isset($response)) {
        $message = new Message();
        $message->setNotification(new Notification($array["title"], $array["body"]));
        $message->setData($data);
        $message->setPriority("high");
        $android_device_tokens = Login::where("user_id", $array["receiver_user_id"])->where("device_os", "android")->get()->pluck("device_token");
        $count = 0;

        if (count($android_device_tokens) > 0) {
          foreach ($android_device_tokens as $device_token) {
            if ($device_token != "") {
              $message->addRecipient(new Device($device_token));
              $count++;
            }
          }
        }

        if ($count != 0) {
          $response = $this->client->send($message);
        }
      }
    }

    /*$responseData = $response->json();

    foreach ($responseData["results"] as $i => $result) {
      if (isset($result["error"])) {
        deleteUserFcmToken($recipients[$i]);
      }
    }*/

    if (!isset($response)) {
      return response()->json([
        "status" => false,
        "message" => "A failure occurred while trying to send notification."
      ], 500);
    } else {
      if (!array_search("notification_id", $array)) {
        $array["notification_id"] = uniqid(rand(), true);
      }
      if ($type == "general") {
        $user_ids = Login::all()->pluck("user_id");
        foreach ($user_ids as $user_id) {
          if (!array_search("notification_id", $array)) {
            $array["notification_id"] = uniqid(rand(), true);
          }
          $array["receiver_user_id"] = $user_id;

          ModelsNotification::Create($array);

          unset($array["notification_id"]);
          unset($array["receiver_user_id"]);
        }
      } else {
        ModelsNotification::Create($array);
      }
    }
  }
}
