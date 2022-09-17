<?php

namespace App\Custom;

use Twilio\Rest\Client;

class OtpHandler
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
      if (request()->header("access_type") == "mobile") {
        if (request()->header("device_os") == "android") {
          return $this->client->verify->v2->services($this->service_sid)
            ->verifications
            ->create($phone_number, "sms", ["appHash" => getenv("APP_HASH")]);
        } else {
          return $this->client->verify->v2->services($this->service_sid)
            ->verifications
            ->create($phone_number, "sms");
        }
      } else {
        return $this->client->verify->v2->services($this->service_sid)
          ->verifications
          ->create($phone_number, "sms");
      }
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
