<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait DeleteTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * DELETE - Nominal case.
     * @param int|null $id
     * @param array $additionalParameters
     * @param string|null $userLogin

     */
    public function doTestDelete(int $id = null, array $additionalParameters = [], string $userLogin = null): void
    {
        $this->doTestGenericDelete(['id' => $id ?? static::defaultEntityId], $additionalParameters, $userLogin);
    }

    /**
     * DELETE - Nominal case.
     * @param array $params
     * @param array $additionalParameters
     * @param string|null $userLogin

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
     * @param int $id
     * @param string|null $userLogin

     */
    public function doTestDeleteNotFound(int $id, string $userLogin = null): void
    {
        $this->doTestGenericDeleteNotFound(['id' => $id], $userLogin);
    }

    /**
     * DELETE - Unexisting entity.
     * @param array $params
     * @param string|null $userLogin

     */
    public function doTestGenericDeleteNotFound(array $params, string $userLogin = null): void
    {
        $apiOutput = self::httpDeleteWithLogin(['name' => static::getDeleteRouteName(), 'params' => $params], $userLogin);
        static::assertApiProblemError($apiOutput, Response::HTTP_NOT_FOUND, [sprintf(ApiProblem::ENTITY_NOT_FOUND, 'entity')]);
    }

    /**
     * DELETE - Error case - Without authentication.
     * @param int|null $id
     */
    public function doTestDeleteWithoutAuthentication(int $id = null): void
    {
        $this->doTestGenericDeleteWithoutAuthentication(['id' => $id ?? static::defaultEntityId]);
    }

    /**
     * DELETE - Error case - Without authentication.
     * @param array $params
     */
    public function doTestGenericDeleteWithoutAuthentication(array $params): void
    {
        $apiOutput = self::httpDelete(['name' => static::getDeleteRouteName(), 'params' => $params], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * DELETE - Error case - Missing right.
     * @param int|null $id
     * @param string|null $userLogin

     */
    public function doTestDeleteWithoutRight(int $id = null, string $userLogin = null): void
    {
        $this->doTestGenericDeleteWithoutRight(['id' => $id ?? static::defaultEntityId], $userLogin);
    }

    /**
     * DELETE - Error case - Missing right.
     * @param array $params
     * @param string|null $userLogin

     */
    public function doTestGenericDeleteWithoutRight(array $params, string $userLogin = null): void
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpDeleteWithLogin(['name' => static::getDeleteRouteName(), 'params' => $params], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);
    }
}
