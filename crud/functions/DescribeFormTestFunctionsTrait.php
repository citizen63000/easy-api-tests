<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait DescribeFormTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * Set $executeSetupOnAllTest to false
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$executeSetupOnAllTest = false;
    }

    protected function doGetTest(string $method, array $params = [], string $userLogin = null)
    {
        $params['method'] = $method;
        $apiOutput = self::httpGetWithLogin(['name' => static::getDescribeFormRouteName(), 'params' => $params], $userLogin);

        $result = $apiOutput->getData();

        $expectedResult = $this->getExpectedResponse(strtolower($method).'.json', 'DescribeForm', $result);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        static::assertAssessableContent($expectedResult, $result);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Nominal case for post form.
     */
    public function doTestGetDescribeFormForPost(array $params = [], string $userLogin = null): void
    {
        $this->doGetTest('POST', $params, $userLogin);
    }

    /**
     * Nominal case for put form.
     */
    public function doTestGetDescribeFormForPut(array $params = [], string $userLogin = null): void
    {
        $this->doGetTest('PUT', $params, $userLogin);
    }

    /**
     * GET - Error case - 401 - Without authentication.
     */
    public function doTestGetDescribeFormWithoutAuthentication(array $params = []): void
    {
        $apiOutput = self::httpGet(['name' => static::getDescribeFormRouteName(), 'params' => $params], false);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $apiOutput->getStatusCode());
    }

    /**
     * GET - Error case - 403 - Missing ADMIN role.
     */
    public function doTestGetDescribeFormWithoutRight(string $userLogin = null, array $params = []): void
    {
        if(null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpGetWithLogin(['name' => static::getDescribeFormRouteName(), 'params' => $params], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [static::getErrorMessageRestrictedAccess()]);
    }
}
