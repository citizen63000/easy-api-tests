<?php

namespace EasyApiTests\crud;

use EasyApiTests\crud\functions\AuthenticationTestFunctionsTrait;
use Symfony\Component\HttpFoundation\Response;

trait AuthenticationTestTrait
{
    use AuthenticationTestFunctionsTrait;

    protected static string $authenticateRouteName = 'fos_user_security_check';
    protected static string $refreshTokenRouteName = 'gesdinet_jwt_refresh_token';
    protected static string $logoutRouteName = 'api_logout';

    protected static function initExecuteSetupOnAllTest(): void
    {
        static::$executeSetupOnAllTest = false;
    }

    public function testAuthenticateWithGoodCredentials(): void
    {
        $params = ['username' => static::USER_TEST_USERNAME, 'password' => static::USER_TEST_PASSWORD];

        // Request
        $apiOutput = self::httpPost(['name' => static::$authenticateRouteName], $params, false);

        // Assert token
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        self::checkAuthenticateResponse($apiOutput->getData());
    }

    public function testAuthenticateWithBadCredentials(): void
    {
        $params = [
            'username' => 'thisusernotexist',
            'password' => 'thisusernotexist',
        ];

        // Request
        $apiOutput = self::httpPost(['name' => static::$authenticateRouteName], $params, false);
        static::assertEquals(Response::HTTP_UNAUTHORIZED, $apiOutput->getStatusCode());
        static::assertEquals(['errors' => ['core.error.bad_credentials']], $apiOutput->getData());
    }

    public function testRefreshToken(): void
    {
        $params = ['username' => static::USER_TEST_USERNAME, 'password' => static::USER_TEST_PASSWORD];

        // get refreshToken
        $apiOutput = self::httpPost(['name' => static::$authenticateRouteName], $params, false);
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        self::arrayHasKey('refreshToken', $result);

        // test refreshToken route
        $apiOutput = self::httpPost(['name' => static::$refreshTokenRouteName], ['refreshToken' => $result['refreshToken']], false);
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        self::checkAuthenticateResponse($apiOutput->getData());
    }

    public function testRefreshTokenWithBadRefreshToken(): void
    {
        // Request
        $apiOutput = self::httpPost(['name' => static::$refreshTokenRouteName], ['refreshToken' => 'imfakerefreshtoken'], false);
        static::assertEquals(Response::HTTP_UNAUTHORIZED, $apiOutput->getStatusCode());
        static::assertEquals(['errors' => ['core.error.bad_credentials']], $apiOutput->getData());
    }

    public function testLogout(): void
    {
        $apiOutput = self::httpPost(['name' => static::$logoutRouteName]);
        static::assertEquals(Response::HTTP_NO_CONTENT, $apiOutput->getStatusCode());
    }
}