<?php

use App\Models\IndividualModel;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Extract the token from the givin Authorization header.
 * 
 * @param string $authorizationHeader `Bearer XXXXXXXX-XXXXXXX`
 * @return string|null
 */
function JWT_extractTokenFromHeader($authorizationHeader): string|null
{
    if (is_null($authorizationHeader)) {
        return null;
    }

    return explode(' ', $authorizationHeader)[1];
}

/**
 * Validates the token authenticity and returns it payload.
 * 
 * @param string $encodedToken
 * @return stdClass|null
 */
function JWT_validateToken(string $encodedToken): stdClass|null
{
    $key = Services::getJWTSecretKey();

    try {
        return JWT::decode($encodedToken, new Key($key, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Create a signed token for the givin ID.
 * 
 * @param int $id
 * @return string
 */
function JWT_signTokenFor(int $id): string
{
    $issuedAtTime = time();
    $timeToLive = MONTH * 1000;
    $exp = $issuedAtTime + $timeToLive;

    $payload = [
        'id' => $id,
        'iat' => $issuedAtTime,
        'exp' => $exp,
    ];

    $key = Services::getJWTSecretKey();
    return JWT::encode($payload, $key, 'HS256');
}
