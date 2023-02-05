<?php

namespace App\Custom;

class CurrencyConverter
{
    private $api_key;

    function __construct()
    {
        $this->api_key = getenv("EXCHANGE_RATE_DATA_API_KEY");
    }

    function convert($amount, $from, $to)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/convert?to=" . $to . "&from=" . $from . "&amount=" . $amount,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
                "apikey: " . $this->api_key
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
