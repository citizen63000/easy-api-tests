<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait CreateTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * POST - Nominal case.
     * @param string $filename
     * @param array $params
     * @param bool $testGetAfterCreate
     * @param string|null $userLogin

     * @throws \Exception
     */
    protected function doTestCreate(string $filename, array $params = [], bool $testGetAfterCreate = true, string $userLogin = null): void
    {
        $data = $this->getDataSent($filename, self::$createActionType);

        // Request
        $apiOutput = self::httpPostWithLogin(['name' => static::getCreateRouteName(), 'params' => $params], $userLogin, $data);

        // Assert result
        static::assertEquals(Response::HTTP_CREATED, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        $expectedResult = $this->getExpectedResponse($filename, static::$createActionType, $result, true);
        static::assertAssessableContent($expectedResult, $result);
        static::assertEquals($expectedResult, $result, "Assert failed for file {$filename}");

        // Get after create
        if($testGetAfterCreate) {
            $this->doTestGetAfterSave($expectedResult['id'], $filename, $userLogin);
        }
    }

    /**
     * Test Invalid submitted data case, fox example invalid data in a field with constraint
     * @throws \Exception
     */
    protected function doTestCreateInvalid(string $filename, array $params = [], array $expectedErrors, int $expectedStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY, string $userLogin = null): void
    {
        $data = $this->getDataSent($filename, self::$createActionType);
        $apiOutput = self::httpPostWithLogin(['name' => static::getCreateRouteName(), 'params' => $params], $userLogin, $data);
        static::assertApiProblemError($apiOutput, $expectedStatusCode, $expectedErrors);
    }

    /**
     * POST - Error case - 401 - Without authentication.
     */
    protected function doTestCreateWithoutAuthentication(array $params = []): void
    {
        $apiOutput = self::httpPost(['name' => static::getCreateRouteName(), 'params' => $params], [], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [static::getErrorMessageJwtNotFound()]);
    }

    /**
     * POST - Error case - 403 - Missing right.
     */
    protected function doTestCreateWithoutRight(array $params = [], string $userLogin = null): void
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpPostWithLogin(['name' => static::getCreateRouteName(), 'params' => $params], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [static::getErrorMessageRestrictedAccess()]);
    }

    /**
     * POST - Error case - 403 - Forbidden action.
     * @throws \Exception
     */
    protected function doTestCreateForbiddenAction(string $filename = null, array $params = [], string $userLogin = null, array $messages = [], $errorCode = Response::HTTP_UNPROCESSABLE_ENTITY): void
    {
        $data = null != $filename ? $this->getDataSent($filename, self::$createActionType) : [];

        $messages = empty($messages) ? [static::getErrorMessageRestrictedAccess()] : $messages;

        $apiOutput = self::httpPostWithLogin(['name' => static::getCreateRouteName(), 'params' => $params], $userLogin, $data);

        static::assertApiProblemError($apiOutput, $errorCode, $messages);
    }
}
