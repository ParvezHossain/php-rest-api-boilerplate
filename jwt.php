<?php

require __DIR__ . "/vendor/autoload.php";

use Firebase\JWT\JWT;

class JWTToken
{
    private string $jwt;

    public function __construct(string $secret_key, string $issuer_claim, string $audience_claim, int $issuedat_claim, int $notbefore_claim, int $expire_claim, array $data)
    {

        // This is for reference purpose
        /* 
        $secret_key = "YOUR_SECRET_KEY";
        $issuer_claim = "THE_ISSUER"; // this can be the servername
        $audience_claim = "THE_AUDIENCE";
        $issuedat_claim = time(); // issued at
        $notbefore_claim = $issuedat_claim + 10; //not before in seconds
        $expire_claim = $issuedat_claim + 60; // expire time in seconds
        */

        $token = [
            "iss" => $issuer_claim,
            "aud" => $audience_claim,
            "iat" => $issuedat_claim,
            "nbf" => $notbefore_claim,
            "exp" => $expire_claim,
            "data" => $data,
        ];

        // Encode the token with HS256 algorithm using your secret key
        $jwt = JWT::encode($token, $secret_key, "HS256");
        $this->jwt = $jwt;
    }

    public static function generateJWTToken(string $secret_key, string $issuer_claim, string $audience_claim, int $issuedat_claim, int $notbefore_claim, int $expire_claim, array $data): string
    {
        $token = new self($secret_key, $issuer_claim, $audience_claim, $issuedat_claim, $notbefore_claim, $expire_claim, $data);
        return $token->jwt;
    }
}

echo JWTToken::generateJWTToken();