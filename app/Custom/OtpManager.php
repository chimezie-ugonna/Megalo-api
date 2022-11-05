<?php

namespace App\Custom;

use Twilio\Rest\Client;

class OtpManager
{

  private $client;
  private $service_sid;

  function __construct()
  {
    $this->client = new Client(getenv("TWILIO_SID"), getenv("TWILIO_TOKEN"));
    $this->service_sid = getenv("TWILIO_SERVICE_SID");
  }

  function sendOtp($type, $email, $phone_number)
  {
    try {
      if ($type == "email") {
        return $this->client->verify->v2->services($this->service_sid)
          ->verifications
          ->create($email, $type);
      } else {
        if (request()->header("access_type") == "mobile") {
          if (request()->header("device_os") == "android") {
            return $this->client->verify->v2->services($this->service_sid)
              ->verifications
              ->create($phone_number, $type, ["appHash" => getenv("APP_HASH")]);
          } else {
            return $this->client->verify->v2->services($this->service_sid)
              ->verifications
              ->create($phone_number, $type);
          }
        } else {
          return $this->client->verify->v2->services($this->service_sid)
            ->verifications
            ->create($phone_number, $type);
        }
      }
    } catch (\Exception) {
      return false;
    }
  }

  function verifyOtp($type, $email, $phone_number, $otp)
  {
    try {
      if ($type == "email") {
        return $this->client->verify->v2->services($this->service_sid)
          ->verificationChecks
          ->create(
            [
              "to" => $email,
              "code" => $otp
            ]
          );
      } else {
        return $this->client->verify->v2->services($this->service_sid)
          ->verificationChecks
          ->create(
            [
              "to" => $phone_number,
              "code" => $otp
            ]
          );
      }
    } catch (\Exception) {
      return false;
    }
  }
}
