<?php

namespace EasyApiTests\Tests\Unit\Crud\Functions;

use EasyApiTests\Core\ApiOutput;
use EasyApiTests\Crud\Functions\GetTestFunctionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class GetTestFunctionsTraitTest extends TestCase
{
    use GetTestFunctionsTrait;

    // Mock required properties
    protected const string baseRouteName = 'test_entity';
    protected const string entityClass = 'App\\Entity\\TestEntity';
    protected const string identifier = 'id';
    protected const string defaultEntityId = '123';

    public function testDoTestGetMethod(): void
    {
        // Verify the method exists and has correct signature
        $reflection = new \ReflectionMethod($this, 'doTestGet');
        $parameters = $reflection->getParameters();

        $this->assertSame('id', $parameters[0]->getName());
        $this->assertSame('filename', $parameters[1]->getName());
        $this->assertSame('userLogin', $parameters[2]->getName());

        // Check default values
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNull($parameters[0]->getDefaultValue());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertSame('nominalCase.json', $parameters[1]->getDefaultValue());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertNull($parameters[2]->getDefaultValue());
    }

    public function testDoTestGenericGetMethod(): void
    {
        $reflection = new \ReflectionMethod($this, 'doTestGenericGet');
        $parameters = $reflection->getParameters();

        $this->assertSame('params', $parameters[0]->getName());
        $this->assertSame('filename', $parameters[1]->getName());
        $this->assertSame('userLogin', $parameters[2]->getName());

        // Check default values
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertSame([], $parameters[0]->getDefaultValue());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertSame('nominalCase.json', $parameters[1]->getDefaultValue());
    }

    public function testGenerateGetRouteParameters(): void
    {
        $params = ['id' => '456', 'filter' => 'active'];
        $result = static::generateGetRouteParameters($params);

        $expected = [
            'name' => 'test_entity_get',
            'params' => ['id' => '456', 'filter' => 'active'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetGetRouteName(): void
    {
        $result = static::getGetRouteName();
        $this->assertSame('test_entity_get', $result);
    }

    public function testTraitUsage(): void
    {
        // Verify that the trait uses CrudFunctionsTestTrait
        $traits = class_uses(GetTestFunctionsTrait::class);
        $this->assertContains('EasyApiTests\Crud\Functions\CrudFunctionsTestTrait', $traits);
    }

    /**
     * Test the methods that would be called in authentication tests.
     */
    public function testAuthenticationTestMethods(): void
    {
        $this->assertTrue(method_exists($this, 'doTestGetWithoutAuthentication'));
        $this->assertTrue(method_exists($this, 'doTestGetWithoutRight'));
        $this->assertTrue(method_exists($this, 'doTestGetNotFound'));
    }

    /**
     * Mock methods for testing.
     */
    protected static function httpGetWithLogin(array $route, ?string $userLogin): ApiOutput
    {
        $response = new Response(json_encode(['id' => 123, 'name' => 'test']), Response::HTTP_OK);

        return new ApiOutput($response, 'application/json');
    }

    protected static function httpGet(array $route, bool $withAuthentication = true): ApiOutput
    {
        $statusCode = $withAuthentication ? Response::HTTP_OK : Response::HTTP_UNAUTHORIZED;
        $content = $withAuthentication ?
            json_encode(['id' => 123]) :
            json_encode(['errors' => ['JWT token not found']]);

        $response = new Response($content, $statusCode);

        return new ApiOutput($response, 'application/json');
    }

    protected function getExpectedResponse(string $filename, string $type, array $result): array
    {
        return ['id' => 123, 'name' => 'test entity'];
    }

    public static function assertAssessableContent(array $expected, array $actual): void
    {
        static::assertSame($expected, $actual);
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

    // Mock constants that the trait might use
    protected const string USER_NORULES_TEST_USERNAME = 'test_user_no_rules';
}
