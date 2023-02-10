<?php

namespace App\Custom;

use App\Models\User;
use DateTime;

class IdentityVerifier
{
    private $api_key;
    private $secret_key;

    function __construct()
    {
        $this->api_key = getenv("IDENFY_API_KEY");
        $this->secret_key = getenv("IDENFY_SECRET_KEY");
    }

    function generateToken($user_id)
    {
        $first_name = User::where("user_id", $user_id)->value("first_name");
        $last_name = User::where("user_id", $user_id)->value("last_name");
        $date_obj = DateTime::createFromFormat("d/m/Y", User::where("user_id", $user_id)->value("dob"));
        $dob = $date_obj->format("Y-m-d");
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ivs.idenfy.com/api/v2/token",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->api_key . ":" . $this->secret_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(["clientId" => $user_id, "firstName" => $first_name, "lastName" => $last_name, "dateOfBirth" => $dob])
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
