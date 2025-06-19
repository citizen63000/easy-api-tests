<?php

namespace EasyApiTests\Tests\Unit\Crud\Functions;

use EasyApiTests\Core\ApiOutput;
use EasyApiTests\Crud\Functions\CreateTestFunctionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateTestFunctionsTraitTest extends TestCase
{
    use CreateTestFunctionsTrait;

    // Mock required properties
    protected const string baseRouteName = 'test_entity';
    protected const string entityClass = 'App\\Entity\\TestEntity';
    protected const string identifier = 'id';

    public function testDoTestCreateWithoutAuthenticationMethod(): void
    {
        // Verify the method exists and is callable
        $this->assertTrue(method_exists($this, 'doTestCreateWithoutAuthentication'));
    }

    public function testDoTestCreateWithoutRightMethod(): void
    {
        // Verify the method exists and is callable
        $this->assertTrue(method_exists($this, 'doTestCreateWithoutRight'));
    }

    public function testGetCreateRouteName(): void
    {
        $result = static::getCreateRouteName();
        $this->assertSame('test_entity_create', $result);
    }

    public function testTraitUsage(): void
    {
        // Verify that the trait uses CrudFunctionsTestTrait
        $traits = class_uses(CreateTestFunctionsTrait::class);
        $this->assertContains('EasyApiTests\Crud\Functions\CrudFunctionsTestTrait', $traits);
    }

    /**
     * Mock method for testing - normally provided by AbstractApiTestCase.
     */
    protected static function httpPostWithLogin(array $route, ?string $userLogin, array $data = []): ApiOutput
    {
        $response = new Response(json_encode(['id' => 123, 'name' => 'test']), Response::HTTP_CREATED);

        return new ApiOutput($response, 'application/json');
    }

    /**
     * Mock method for testing - normally provided by AbstractApiTestCase.
     */
    protected static function httpPost(array $route, array $data = [], bool $withAuthentication = true): ApiOutput
    {
        $statusCode = $withAuthentication ? Response::HTTP_UNAUTHORIZED : Response::HTTP_CREATED;
        $content = $withAuthentication ?
            json_encode(['errors' => ['JWT token not found']]) :
            json_encode(['id' => 123]);

        $response = new Response($content, $statusCode);

        return new ApiOutput($response, 'application/json');
    }

    /**
     * Mock method for testing.
     */
    protected function getCurrentDir(): string
    {
        return '/tmp/test';
    }

    /**
     * Mock method for testing.
     */
    protected function getDataSent(string $filename, string $type, ?array $defaultContent = null): array
    {
        return ['name' => 'test entity', 'description' => 'test description'];
    }

    /**
     * Mock method for testing.
     */
    protected function getExpectedResponse(string $filename, string $type, array $result, bool $dateProtection = false): array
    {
        return ['id' => 123, 'name' => 'test entity'];
    }

    /**
     * Mock method for testing.
     */
    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        parent::assertEquals($expected, $actual, $message);
    }

    /**
     * Mock method for testing.
     */
    public static function assertAssessableContent(array $expected, array $actual): void
    {
        // Mock implementation for testing
        static::assertSame($expected, $actual);
    }

    /**
     * Mock method for testing.
     */
    protected function doTestGetAfterSave(string $id, string $filename, ?string $userLogin = null): void
    {
        // Mock implementation - just verify method can be called
    }

    public function testDoTestCreateRouteGeneration(): void
    {
        // Test that doTestCreate would use correct route parameters
        $params = ['category' => 'test'];
        $expectedRoute = ['name' => 'test_entity_create', 'params' => $params];

        // Since we can't easily test the full doTestCreate method without mocking many dependencies,
        // we'll test the route generation logic that it uses
        $routeName = static::getCreateRouteName();
        $actualRoute = ['name' => $routeName, 'params' => $params];

        $this->assertSame($expectedRoute, $actualRoute);
    }

    /**
     * Test the structure and expected behavior of doTestCreate method.
     */
    public function testDoTestCreateMethodStructure(): void
    {
        $reflection = new \ReflectionMethod($this, 'doTestCreate');
        $parameters = $reflection->getParameters();

        // Verify method signature
        $this->assertSame('filename', $parameters[0]->getName());
        $this->assertSame('params', $parameters[1]->getName());
        $this->assertSame('testGetAfterCreate', $parameters[2]->getName());
        $this->assertSame('userLogin', $parameters[3]->getName());

        // Verify default values
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertSame([], $parameters[1]->getDefaultValue());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertTrue($parameters[2]->getDefaultValue());
        $this->assertTrue($parameters[3]->isDefaultValueAvailable());
        $this->assertNull($parameters[3]->getDefaultValue());
    }
}
