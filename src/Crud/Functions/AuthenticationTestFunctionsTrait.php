<?php

namespace EasyApiTests\Crud\Functions;

use Namshi\JOSE\JWS;

trait AuthenticationTestFunctionsTrait
{
    use CrudFunctionsTestTrait;

    protected function checkAuthenticateResponse(array $response): void
    {
        self::arrayHasKey('token', $response);
        self::arrayHasKey('refreshToken', $response);
        self::checkPayloadContent($token = JWS::load($response['token'])->getPayload());
    }

    protected function checkPayloadContent(array $payload): void
    {
        self::assertArrayHasKey('iat', $payload);
        self::assertArrayHasKey('exp', $payload);
        self::assertEquals($payload['iat'] + static::getContainer()->getParameter('jwt_token_ttl'), $payload['exp']);
    }
}
