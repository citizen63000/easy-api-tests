<?php

namespace EasyApiTests\Tests\Unit\Crud\Functions;

use EasyApiTests\Core\ApiOutput;
use EasyApiTests\Crud\Functions\DeleteTestFunctionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DeleteTestFunctionsTraitTest extends TestCase
{
    use DeleteTestFunctionsTrait;

    // Mock required properties
    protected const string baseRouteName = 'test_entity';
    protected const string entityClass = 'App\\Entity\\TestEntity';
    protected const string identifier = 'id';
    protected const string defaultEntityId = '123';

    public function testGetDeleteRouteName(): void
    {
        $result = static::getDeleteRouteName();
        $this->assertSame('test_entity_delete', $result);
    }

    public function testGenerateDeleteRouteParameters(): void
    {
        $params = ['id' => '456', 'force' => true];
        $result = static::generateDeleteRouteParameters($params);

        $expected = [
            'name' => 'test_entity_delete',
            'params' => ['id' => '456', 'force' => true],
        ];

        $this->assertSame($expected, $result);
    }

    public function testTraitUsage(): void
    {
        // Verify that the trait uses CrudFunctionsTestTrait
        $traits = class_uses(DeleteTestFunctionsTrait::class);
        $this->assertContains('EasyApiTests\Crud\Functions\CrudFunctionsTestTrait', $traits);
    }

    public function testAuthenticationTestMethods(): void
    {
        $this->assertTrue(method_exists($this, 'doTestDeleteWithoutAuthentication'));
        $this->assertTrue(method_exists($this, 'doTestDeleteWithoutRight'));
        $this->assertTrue(method_exists($this, 'doTestDeleteNotFound'));
    }

    /**
     * Mock methods for testing.
     */
    protected static function httpDeleteWithLogin(array $route, ?string $userLogin): ApiOutput
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);

        return new ApiOutput($response, 'application/json');
    }

    protected static function httpDelete(array $route, bool $withAuthentication = true): ApiOutput
    {
        $statusCode = $withAuthentication ? Response::HTTP_NO_CONTENT : Response::HTTP_UNAUTHORIZED;
        $content = $withAuthentication ? '' : json_encode(['errors' => ['JWT token not found']]);

        $response = new Response($content, $statusCode);

        return new ApiOutput($response, 'application/json');
    }

    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        parent::assertEquals($expected, $actual, $message);
    }

    protected static function assertApiProblemError(ApiOutput $apiOutput, int $statusCode, array $errors): void
    {
        static::assertSame($statusCode, $apiOutput->getStatusCode());
        $data = $apiOutput->getData();
        static::assertArrayHasKey('errors', $data);
        static::assertSame($errors, $data['errors']);
    }

    protected static function getErrorMessageJwtNotFound(): string
    {
        return 'JWT token not found';
    }

    protected static function getErrorMessageRestrictedAccess(): string
    {
        return 'Access denied';
    }

    protected static function getErrorMessageEntityNotFound(): string
    {
        return 'Entity not found';
    }

    // Mock constants
    protected const USER_NORULES_TEST_USERNAME = 'test_user_no_rules';
}
