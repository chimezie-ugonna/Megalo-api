<?php

namespace App\Custom;

use App\Models\User;

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
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ivs.idenfy.com/api/v2/token",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->api_key . ":" . $this->secret_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => ["clientId" => $user_id, "firstName" => $first_name, "lastName" => $last_name],
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
