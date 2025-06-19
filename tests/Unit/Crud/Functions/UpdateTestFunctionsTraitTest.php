<?php

namespace EasyApiTests\Tests\Unit\Crud\Functions;

use EasyApiTests\Core\ApiOutput;
use EasyApiTests\Crud\Functions\UpdateTestFunctionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateTestFunctionsTraitTest extends TestCase
{
    use UpdateTestFunctionsTrait;

    // Mock required properties
    protected const string baseRouteName = 'test_entity';
    protected const string entityClass = 'App\\Entity\\TestEntity';
    protected const string identifier = 'id';
    protected const string defaultEntityId = '123';

    public function testDoTestUpdateMethodSignature(): void
    {
        $reflection = new \ReflectionMethod($this, 'doTestUpdate');
        $parameters = $reflection->getParameters();

        $this->assertSame('id', $parameters[0]->getName());
        $this->assertSame('filename', $parameters[1]->getName());
        $this->assertSame('params', $parameters[2]->getName());
        $this->assertSame('userLogin', $parameters[3]->getName());
        $this->assertSame('doGetTest', $parameters[4]->getName());
        $this->assertSame('expectedResponseCode', $parameters[5]->getName());

        // Check default values - some parameters might not have defaults in trait methods
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertSame([], $parameters[2]->getDefaultValue());
        $this->assertTrue($parameters[3]->isOptional());
        $this->assertTrue($parameters[4]->isDefaultValueAvailable());
        $this->assertTrue($parameters[4]->getDefaultValue());
        $this->assertTrue($parameters[5]->isDefaultValueAvailable());
        $this->assertSame(Response::HTTP_OK, $parameters[5]->getDefaultValue());
    }

    public function testGetUpdateRouteName(): void
    {
        $result = static::getUpdateRouteName();
        $this->assertSame('test_entity_update', $result);
    }

    public function testTraitUsage(): void
    {
        // Verify that the trait uses CrudFunctionsTestTrait
        $traits = class_uses(UpdateTestFunctionsTrait::class);
        $this->assertContains('EasyApiTests\Crud\Functions\CrudFunctionsTestTrait', $traits);
    }

    public function testAuthenticationTestMethods(): void
    {
        $this->assertTrue(method_exists($this, 'doTestUpdateWithoutAuthentication'));
        $this->assertTrue(method_exists($this, 'doTestUpdateWithoutRight'));
    }

    /**
     * Mock methods for testing.
     */
    protected static function httpPutWithLogin(array $route, ?string $userLogin, array $data = []): ApiOutput
    {
        $response = new Response(json_encode(['id' => 123, 'name' => 'updated']), Response::HTTP_OK);

        return new ApiOutput($response, 'application/json');
    }

    protected static function httpPut(array $route, array $data = [], bool $withAuthentication = true): ApiOutput
    {
        $statusCode = $withAuthentication ? Response::HTTP_OK : Response::HTTP_UNAUTHORIZED;
        $content = $withAuthentication ?
            json_encode(['id' => 123]) :
            json_encode(['errors' => ['JWT token not found']]);

        $response = new Response($content, $statusCode);

        return new ApiOutput($response, 'application/json');
    }

    protected function getDataSent(string $filename, string $type, ?array $defaultContent = null): array
    {
        return ['name' => 'updated entity', 'description' => 'updated description'];
    }

    protected function getExpectedResponse(string $filename, string $type, array $result, bool $dateProtection = false): array
    {
        return ['id' => 123, 'name' => 'updated entity'];
    }

    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        parent::assertEquals($expected, $actual, $message);
    }

    public static function assertAssessableContent(array $expected, array $actual): void
    {
        static::assertSame($expected, $actual);
    }

    protected function doTestGetAfterSave(string $id, string $filename, ?string $userLogin = null): void
    {
        // Mock implementation
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

    // Mock constants
    protected const USER_NORULES_TEST_USERNAME = 'test_user_no_rules';
}
