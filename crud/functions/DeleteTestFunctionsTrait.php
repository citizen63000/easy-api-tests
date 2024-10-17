<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait DeleteTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * DELETE - Nominal case.
     */
    public function doTestDelete(string $id = null, array $additionalParameters = [], string $userLogin = null): void
    {
        $this->doTestGenericDelete([static::identifier => $id ?? static::defaultEntityId], $additionalParameters, $userLogin);
    }

    /**
     * DELETE - Nominal case.
     */
    public function doTestGenericDelete(array $params, array $additionalParameters = [], string $userLogin = null): void
    {
        $allParams = $params + $additionalParameters;

        // count before delete
        $apiOutput = self::httpGetWithLogin(static::generateGetListRouteParameters(), $userLogin);
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $nbResults = (int) $apiOutput->getHeaderLine('X-Total-Results');

        // delete entity
        $apiOutput = self::httpDeleteWithLogin(static::generateDeleteRouteParameters($allParams), $userLogin);
        static::assertEquals(Response::HTTP_NO_CONTENT, $apiOutput->getStatusCode());

        // try to get after delete
        $apiOutput = self::httpGetWithLogin(static::generateGetRouteParameters($params), $userLogin);
        static::assertEquals(Response::HTTP_NOT_FOUND, $apiOutput->getStatusCode());

        // count after delete
        $apiOutput = self::httpGetWithLogin(static::generateGetListRouteParameters(), $userLogin);
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        static::assertEquals($nbResults-1, $apiOutput->getHeaderLine('X-Total-Results'));
    }

    /**
     * DELETE - Unexisting entity.
     */
    public function doTestDeleteNotFound(string $id, string $userLogin = null): void
    {
        $this->doTestGenericDeleteNotFound([static::identifier => $id], $userLogin);
    }

    /**
     * DELETE - Unexisting entity.
     */
    public function doTestGenericDeleteNotFound(array $params, string $userLogin = null): void
    {
        $apiOutput = self::httpDeleteWithLogin(['name' => static::getDeleteRouteName(), 'params' => $params], $userLogin);
        static::assertApiProblemError($apiOutput, Response::HTTP_NOT_FOUND, [static::getErrorMessageEntityNotFound()]);
    }

    /**
     * DELETE - Error case - Without authentication.
     */
    public function doTestDeleteWithoutAuthentication(string $id = null): void
    {
        $this->doTestGenericDeleteWithoutAuthentication([static::identifier => $id ?? static::defaultEntityId]);
    }

    /**
     * DELETE - Error case - Without authentication.
     */
    public function doTestGenericDeleteWithoutAuthentication(array $params): void
    {
        $apiOutput = self::httpDelete(['name' => static::getDeleteRouteName(), 'params' => $params], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [static::getErrorMessageJwtNotFound()]);
    }

    /**
     * DELETE - Error case - Missing right.
     */
    public function doTestDeleteWithoutRight(string $id = null, string $userLogin = null): void
    {
        $this->doTestGenericDeleteWithoutRight([static::identifier => $id ?? static::defaultEntityId], $userLogin);
    }

    /**
     * DELETE - Error case - Missing right.
     */
    public function doTestGenericDeleteWithoutRight(array $params, string $userLogin = null): void
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpDeleteWithLogin(['name' => static::getDeleteRouteName(), 'params' => $params], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [static::getErrorMessageRestrictedAccess()]);
    }
}
