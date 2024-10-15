<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait GetTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * GET - Nominal case.
     */
    public function doTestGet(string $id = null, string $filename = 'nominalCase.json', string $userLogin = null): void
    {
        self::doTestGenericGet([static::identifier => $id ?? static::defaultEntityId], $filename, $userLogin);
    }

    public function doTestGenericGet(array $params = [], string $filename = 'nominalCase.json', string $userLogin = null)
    {
        $apiOutput = self::httpGetWithLogin(static::generateGetRouteParameters($params), $userLogin);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        $expectedResult = $this->getExpectedResponse($filename, static::$getActionType, $result);

        static::assertAssessableContent($expectedResult, $result);
        static::assertEquals($expectedResult, $result, "Assert failed for file {$filename}");
    }

    /**
     * GET - Error case - not found.
     */
    public function doTestGetNotFound(string $id = null, string $userLogin = null): void
    {
        self::doTestGenericGetNotFound([static::identifier => $id ?? 99999999], $userLogin);
    }

    public function doTestGenericGetNotFound(array $params = [], string $userLogin = null): void
    {
        $apiOutput = self::httpGetWithLogin(static::generateGetRouteParameters($params), $userLogin);
        static::assertApiProblemError($apiOutput, Response::HTTP_NOT_FOUND, [sprintf(ApiProblem::ENTITY_NOT_FOUND, 'entity')]);
    }

    /**
     * GET - Error case - Without authentication.
     */
    public function doTestGetWithoutAuthentication(string $id = null): void
    {
        self::doTestGenericGetWithoutAuthentication([static::identifier => $id ?? static::defaultEntityId]);
    }

    /**
     * GET - Error case - Without authentication.
     */
    public function doTestGenericGetWithoutAuthentication(array $params = []): void
    {
        $apiOutput = self::httpGet(static::generateGetRouteParameters($params), false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * GET - Error case - Missing right.
     */
    public function doTestGetWithoutRight(string $id = null, string $userLogin = null): void
    {
        self::doTestGenericGetWithoutRight([static::identifier => $id ?? static::defaultEntityId], $userLogin);
    }

    /**
     * GET - Error case - Missing right.
     */
    public function doTestGenericGetWithoutRight(array $params = [], string $userLogin = null): void
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpGetWithLogin(static::generateGetRouteParameters($params), $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);
    }
}
