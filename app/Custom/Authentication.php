<?php

namespace App\Custom;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Authentication
{
    private $token_key;

    function __construct()
    {
        $this->token_key = getenv("APP_KEY");
    }

    function encode($data, $expirable = false, $ttl = 1800)
    {
        $payload = [
            "iat" => time(),
            "data" => $data
        ];
        if ($expirable) {
            $payload["exp"] = time() + $ttl;
        }
        return JWT::encode($payload, $this->token_key, "HS512");
    }

    function decode($token)
    {
        try {
            return (array) (JWT::decode($token, new Key($this->token_key, "HS512")));
        } catch (\Exception) {
            return false;
        }
    }
}
