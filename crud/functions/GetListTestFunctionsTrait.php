<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\ApiProblem;
use EasyApiTests\ApiOutput;
use Symfony\Component\HttpFoundation\Response;

trait GetListTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * GET LIST - Nominal case.
     */
    protected function doTestGetList(string $filename, array $params = [], string $userLogin = null): ApiOutput
    {
        $apiOutput = self::httpGetWithLogin(['name' => static::getGetListRouteName(), 'params' => $params], $userLogin);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());

        $result = $apiOutput->getData();
        $expectedResult = $this->getExpectedResponse($filename, self::$getListActionType, $apiOutput->getData());
        static::assertAssessableContent($expectedResult, $result);
        static::assertEquals($expectedResult, $result, "Assert failed for file {$filename}");

        return $apiOutput;
    }

    protected function doTestGetListPaginate(string $filename, int $page = null, int $limit = null, array $params = [], string $userLogin = null): ?ApiOutput
    {
        try {
            $pagination = [];
            if (null !== $page) {
                $pagination['page'] = $page;
            }
            if (null !== $limit) {
                $pagination['limit'] = $limit;
            }
            return $this->doTestGetList($filename, $pagination + $params, $userLogin);
        } catch (ReflectionException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    protected function doTestGetListFiltered(string $filename, int $page = null, int $limit = null, array $filters = [], string $sort = null, array $params = [], string $userLogin = null): ApiOutput
    {
        return $this->doTestGetListPaginate($filename, $page, $limit, $filters + ['sort' => $sort] + $params, $userLogin);
    }

    /**
     * GET - Error case - 401 - Without authentication.
     */
    protected function doTestGetWithoutAuthentication(): ApiOutput
    {
        $apiOutput = self::httpGet(['name' => static::getGetListRouteName(), 'params' => []], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);

        return $apiOutput;
    }

    /**
     * GET - Error case - 403 - Missing right.
     */
    protected function doTestGetWithoutRight(string $userLogin = null): ApiOutput
    {
        if (null === $userLogin) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
        }

        $apiOutput = self::httpGetWithLogin(['name' => static::getGetListRouteName(), 'params' => []], $userLogin);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);

        return $apiOutput;
    }
}