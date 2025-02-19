<?php

use App\Models\IndividualModel;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function JWT_extractTokenFromHeader($authorizationHeader): string|null
{
    if (is_null($authorizationHeader)) {
        return null;
    }

    return explode(' ', $authorizationHeader)[1];
}

function JWT_validateToken(string $encodedToken): array|null
{
    $key = Services::getJWTSecretKey();
    $individualModel = new IndividualModel();

    try {
        $decodedToken = JWT::decode($encodedToken, new Key($key, 'HS256'));
        $individual = $individualModel->find($decodedToken->id);

        return [
            'payload'    => $decodedToken,
            'individual' => $individual
        ];
    } catch (Exception $e) {
        log_message('critical', $e->getMessage());
        return null;
    }
}

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
