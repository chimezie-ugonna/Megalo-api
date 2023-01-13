<?php

namespace App\Custom;

use Twilio\Rest\Client;
use SendGrid\Mail\From;
use SendGrid\Mail\To;
use SendGrid\Mail\Mail;

class EmailManager
{

    private $client;
    private $service_sid;
    private $sendgrid;
    private $from_email;
    private $from_name;

    function __construct()
    {
        $this->client = new Client(getenv("TWILIO_SID"), getenv("TWILIO_TOKEN"));
        $this->service_sid = getenv("TWILIO_SERVICE_SID");
        $this->sendgrid = new \SendGrid(getenv("SENDGRID_API_KEY"));
        $this->from_email = "support@investmegalo.com";
        $this->from_name = "Megalo";
    }

    function sendOtp($email)
    {
        try {
            return $this->client->verify->v2->services($this->service_sid)
                ->verifications
                ->create($email, "email");
        } catch (\Exception) {
            return false;
        }
    }

    function verifyOtp($email, $otp)
    {
        try {
            return $this->client->verify->v2->services($this->service_sid)
                ->verificationChecks
                ->create(
                    [
                        "to" => $email,
                        "code" => $otp
                    ]
                );
        } catch (\Exception) {
            return false;
        }
    }

    function sendInsufficientFundMessage($amount, $admin_emails)
    {
        $from = new From($this->from_email, $this->from_name);
        $tos = [];
        if (count($admin_emails) > 0) {
            $count = 0;
            foreach ($admin_emails as $email) {
                $tos[$count] = new To(
                    $email,
                    null,
                    [
                        "amount" => $amount
                    ]
                );
                $count++;
            }

            $email = new Mail(
                $from,
                $tos
            );
            $email->setTemplateId("d-b8a32ed233e54e06a5fd107ca80eefd5");
            try {
                return $this->sendgrid->send($email);
            } catch (\Exception) {
                return false;
            }
        } else {
            return false;
        }
    }
}
