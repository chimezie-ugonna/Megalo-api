<?php

namespace App\Custom;

use Twilio\Rest\Client;

class SendSms
{

  private $client;
  private $service_sid;

  function __construct()
  {
    $this->client = new Client(getenv("TWILIO_SID"), getenv("TWILIO_TOKEN"));
    $this->service_sid = getenv("TWILIO_SERVICE_SID");
  }

  function sendOtp($phone_number)
  {
    try {
      return $this->client->verify->v2->services($this->service_sid)
        ->verifications
        ->create($phone_number, "sms");
    } catch (\Exception) {
      return false;
    }
  }

  function verifyOtp($phone_number, $otp)
  {
    try {
      return $this->client->verify->v2->services($this->service_sid)
        ->verificationChecks
        ->create(
          [
            "to" => $phone_number,
            "code" => $otp
          ]
        );
    } catch (\Exception) {
      return false;
    }
  }
}