<?php

namespace App\Custom;

use Twilio\Rest\Client;

class SendSms
{

  private $sender_id;
  private $sid;
  private $token;

  function __construct()
  {
    $this->sender_id = getenv("TWILIO_SENDER_ID");
    $this->sid = getenv("TWILIO_SID");
    $this->token = getenv("TWILIO_TOKEN");
  }

  function sendOtp($phone_number)
  {
    try {
      $otp = rand(100000, 999999);
      $client = new Client($this->sid, $this->token);
      $message = $client->messages->create(
        $phone_number,
        [
          'from' => $this->sender_id,
          'body' => 'This is your Megalo otp ' . strval($otp)
        ]
      );
      if (isset($message)) {
        return $otp;
      } else {
        return false;
      }
    } catch (\Exception) {
      return false;
    }
  }
}
