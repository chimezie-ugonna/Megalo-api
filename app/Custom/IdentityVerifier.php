<?php

namespace App\Custom;

class IdentityVerifier
{
    function run($type, $id)
    {
        if ($type == "generateToken") {
            $url_end = "token";
            $param = "clientId";
        } else {
            $url_end = "delete";
            $param = "scanRef";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ivs.idenfy.com/api/v2/" . $url_end,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => getenv("IDENFY_API_KEY") . ":" . getenv("IDENFY_SECRET_KEY"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([$param => $id, "expiryTime" => 60, "sessionLength" => 61])
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
