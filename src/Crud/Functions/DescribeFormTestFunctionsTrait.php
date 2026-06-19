<?php

namespace EasyApiTests\Crud\Functions;

use Symfony\Component\HttpFoundation\Response;

trait DescribeFormTestFunctionsTrait
{
    use CrudFunctionsTestTrait;

    /**
     * Set $executeSetupOnAllTest to false.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$executeSetupOnAllTest = false;
    }

    protected function doGetTest(string $method, array $params = [], ?string $userLogin = null, ?string $filename = null)
    {
        if (!$filename) {
            $filename = mb_strtolower($method).'.json';
        }
        $params['method'] = $method;
        $apiOutput = self::httpGetWithLogin(['name' => static::getDescribeFormRouteName(), 'params' => $params], $userLogin);

        $result = $apiOutput->getData();

        $expectedResult = $this->getExpectedResponse($filename, 'DescribeForm', $result);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        static::assertAssessableContent($expectedResult, $result);
        if (static::strictComparison) {
            static::assertEquals($expectedResult, $result, "Get for DescribeForm failed for file {$filename}");
        } else {
            static::assertContainsSubset($expectedResult, $result, "Get for DescribeForm failed for file {$filename}");
        }
    }

    /**
     * Nominal case for post form.
     */
    public function doTestGetDescribeFormForPost(array $params = [], ?string $userLogin = null): void
    {
        $this->doGetTest('POST', $params, $userLogin);
    }

    /**
     * Nominal case for put form.
     */
    public function doTestGetDescribeFormForPut(array $params = [], ?string $userLogin = null): void
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
    public function doTestGetDescribeFormWithoutRight(?string $userLogin = null, array $params = []): void
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpGetWithLogin(['name' => static::getDescribeFormRouteName(), 'params' => $params], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [static::getErrorMessageRestrictedAccess()]);
    }
}
