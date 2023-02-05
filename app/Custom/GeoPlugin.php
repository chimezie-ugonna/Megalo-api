<?php

namespace App\Custom;

class GeoPlugin
{
    function getIpAddress()
    {
        $ipaddress = "";
        if (isset($_SERVER["HTTP_CLIENT_IP"]))
            $ipaddress = $_SERVER["HTTP_CLIENT_IP"];
        else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $ipaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if (isset($_SERVER["HTTP_X_FORWARDED"]))
            $ipaddress = $_SERVER["HTTP_X_FORWARDED"];
        else if (isset($_SERVER["HTTP_FORWARDED_FOR"]))
            $ipaddress = $_SERVER["HTTP_FORWARDED_FOR"];
        else if (isset($_SERVER["HTTP_FORWARDED"]))
            $ipaddress = $_SERVER["HTTP_FORWARDED"];
        else if (isset($_SERVER["REMOTE_ADDR"]))
            $ipaddress = $_SERVER["REMOTE_ADDR"];
        else
            $ipaddress = "Unknown";
        return $ipaddress;
    }

    function getIpAddressDetails($ip, $detail_type)
    {
        $ip_detail = json_decode(file_get_contents(
            "http://www.geoplugin.net/json.gp?ip=" . $ip
        ));

        if (isset($ip_detail)) {
            switch ($detail_type) {
                case "Country":
                    return $ip_detail->geoplugin_countryName;
                    break;
                case "City":
                    return $ip_detail->geoplugin_city;
                    break;
                case "Continent":
                    return $ip_detail->geoplugin_continentName;
                    break;
                case "Latitude":
                    return $ip_detail->geoplugin_latitude;
                    break;
                case "Longitude":
                    return $ip_detail->geoplugin_longitude;
                    break;
                case "Currency Symbol":
                    return $ip_detail->geoplugin_currencySymbol;
                    break;
                case "Currency Code":
                    return $ip_detail->geoplugin_currencyCode;
                    break;
                case "Timezone":
                    return $ip_detail->geoplugin_timezone;
                    break;

                default:
                    return false;
                    break;
            }
        } else {
            return false;
        }
    }

    function convertCurrency($from, $to, $amount)
    {
        $ip_detail = unserialize(file_get_contents(
            "http://www.geoplugin.net/currency/php.gp?from=" . $from . "&to=" . $to . "&amount=" . $amount
        ));

        if (isset($ip_detail)) {
            return $ip_detail;
        } else {
            return false;
        }
    }
}
