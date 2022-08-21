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

    function encode($data)
    {
        $payload = [
            'iat' => time(),
            'data' => $data
        ];
        return JWT::encode($payload, $this->token_key, 'HS512');
    }

    function decode($token)
    {
        try {
            return (array) (JWT::decode($token, new Key($this->token_key, 'HS512')));
        } catch (\Exception) {
            return false;
        }
    }
}