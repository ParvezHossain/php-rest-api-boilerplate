<?php

require __DIR__ . "/vendor/autoload.php";

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class JWTToken
{
    private string $jwt;

    /**
     * Constructor method for creating JWT tokens.
     * 
     * @param array $data An array containing the data to be encoded in the JWT token
     */
    public function __construct(array $data)
    {
        // Get the current timestamp
        $date = new DateTimeImmutable();
        // Set the token expiration time to 59 minutes from the current time
        $expire_at = $date->modify('+59 minutes')->getTimestamp();

        // Build the token payload
        $token = [
            'iat'  => $date->getTimestamp(),         // Issued at: time when the token was generated
            'iss'  => 'localhost',                   // Issuer
            'nbf'  => $date->getTimestamp(),         // Not before
            'exp'  => $expire_at,                    // Expire
            "data" => $data,                         // Data to be encoded in the token
        ];

        // Encode the token with HS512 algorithm using the secret key and save it to the class property
        $this->jwt = JWT::encode($token, SECRET_KEY, HASH_ALGORITHM);
    }

    /**
     * Method for generating JWT tokens.
     * 
     * @param array $data An array containing the data to be encoded in the JWT token
     * @return string The generated JWT token
     */
    public static function generateJWTToken(array $data): string
    {
        $token = new self($data);
        return $token->jwt;
    }

    /**
     * Method for verifying JWT tokens.
     * 
     * @param string $token The JWT token to be verified
     */
    public static function verifyJWTToken(string $token): void
    {
        $now = new DateTimeImmutable();

        try {
            // Decode the token using the secret key and check if it has expired
            $decoded = JWT::decode($token, new Key(SECRET_KEY, HASH_ALGORITHM));
            if ($decoded->nbf > $now->getTimestamp() || $decoded->exp < $now->getTimestamp()) {
                http_response_code(401);
                echo json_encode(['error' => 'Token expired']);
            }
        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token expired']);
            exit(1);
        } catch (SignatureInvalidException $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token signature is invalid']);
            exit(1);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit(1);
        }
    }
}
