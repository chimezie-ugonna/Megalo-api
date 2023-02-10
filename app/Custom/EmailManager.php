<?php

namespace App\Custom;

use App\Models\User;
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

    function sendOtp($email, $data)
    {
        try {
            return $this->client->verify->v2->services($this->service_sid)
                ->verifications
                ->create($email, "email", [
                    "channelConfiguration" => [
                        "substitutions" => [
                            "subject" => $data["subject"],
                            "title" => $data["title"],
                            "body" => $data["body"],
                            "footer" => $data["footer"]
                        ]
                    ],
                ]);
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

    function sendInsufficientFundMessage($amount, $admin_user_ids)
    {
        $from = new From($this->from_email, $this->from_name);
        $tos = [];
        if (count($admin_user_ids) > 0) {
            $count = 0;
            foreach ($admin_user_ids as $user_id) {
                $email = User::where("user_id", $user_id)->value("email");
                if (sizeof(User::find($user_id)->login()->get()) > 0) {
                    $ip_address = User::find($user_id)->login()->latest()->first()->ip_address;
                    $localization = new Localization($ip_address, ["amount" => $amount]);
                    $subject = $localization->getText("insufficient_fund_email_subject");
                    $title = $localization->getText("insufficient_fund_email_title");
                    $body = $localization->getText("insufficient_fund_email_body");
                    $footer = $localization->getText("insufficient_fund_email_footer");
                } else {
                    $localization = new Localization("", ["amount" => $amount]);
                    $subject = $localization->getText("insufficient_fund_email_subject");
                    $title = $localization->getText("insufficient_fund_email_title");
                    $body = $localization->getText("insufficient_fund_email_body");
                    $footer = $localization->getText("insufficient_fund_email_footer");
                }
                $tos[$count] = new To(
                    $email,
                    null,
                    [
                        "subject" => $subject,
                        "title" => $title,
                        "body" => $body,
                        "footer" => $footer
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
