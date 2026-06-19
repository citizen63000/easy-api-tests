<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiCore\Util\ApiProblem;
use EasyApiTests\Core\ApiTestAssertionsTrait;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class ApiTestAssertionsTraitTest extends TestCase
{
    use ApiTestAssertionsTrait;

    protected static array $additionalAssessableFunctions = [];
    protected static string $errorPrefix = '';
    protected static array $assessableFunctions = [];
    protected static string $regexp_uuid = '[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+';
    protected static string $regexp_uid = '[a-zA-Z0-9]+';

    protected function setUp(): void
    {
        parent::setUp();
        static::$additionalAssessableFunctions = [];
    }

    protected static function getDomainUrl(): string
    {
        return 'https://example.com';
    }

    public function testAddAdditionalAssessableFunction(): void
    {
        $this->addAdditionalAssessableFunction('testFunction');
        $this->assertContains('testFunction', static::$additionalAssessableFunctions);
    }

    public function testAssertArraysAreSimilar(): void
    {
        // Arrays with same elements but different order
        $array1 = [1, 2, 3];
        $array2 = [3, 1, 2];

        static::assertArraysAreSimilar($array1, $array2);
    }

    public function testAssertAssociativeArraysAreSimilar(): void
    {
        // Same associative arrays
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['b' => 2, 'a' => 1];

        static::assertAssociativeArraysAreSimilar($array1, $array2);
    }

    public function testAssertAssociativeArraysAreSimilarFailsWithDifferentSizes(): void
    {
        $array1 = ['a' => 1, 'b' => 2, 'c' => 3];
        $array2 = ['a' => 1, 'b' => 2];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The array have not the same size');

        static::assertAssociativeArraysAreSimilar($array1, $array2);
    }

    public function testAssertAssociativeArraysAreSimilarFailsWithMissingKey(): void
    {
        $array1 = ['a' => 1, 'c' => 3];
        $array2 = ['a' => 1, 'b' => 2];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The second array have not the key 'c'");

        static::assertAssociativeArraysAreSimilar($array1, $array2);
    }

    public function testAssertAssociativeArraysAreSimilarFailsWithDifferentValues(): void
    {
        $array1 = ['a' => 1, 'b' => 3];
        $array2 = ['a' => 1, 'b' => 2];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Values for 'b' key do not match");

        static::assertAssociativeArraysAreSimilar($array1, $array2);
    }

    // Updated tests for assertFields
    public function testAssertFields(): void
    {
        $fields = ['id', 'name', 'email'];
        $entity = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        static::assertFields($fields, $entity);
    }

    public function testAssertFieldsWithDifferentFieldCount(): void
    {
        $fields = ['id', 'name'];
        $entity = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Expected field count');

        static::assertFields($fields, $entity);
    }

    public function testAssertFieldsWithMissingField(): void
    {
        $fields = ['id', 'name', 'email', 'phone'];
        $entity = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Expected field count : 4, get 3');

        static::assertFields($fields, $entity);
    }

    public function testAssertFieldsWithAssertOnlyFieldsTrue(): void
    {
        $fields = ['id', 'name'];
        $entity = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        // Should succeed because assertOnlyFields=true ignores field count verification
        static::assertFields($fields, $entity, true);
    }

    public function testAssertFieldsWithAtLeastTrue(): void
    {
        $fields = ['id', 'name'];
        $entity = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        // Should succeed because atLeast=true allows the entity to have more fields
        static::assertFields($fields, $entity, false, true);
    }

    public function testAssertFieldsWithNullEntity(): void
    {
        $fields = ['id', 'name'];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The entity should not be null !');

        static::assertFields($fields);
    }

    public function testAssertContainsSubset(): void
    {
        $expected = ['id' => 1, 'name' => 'John'];
        $actual = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        static::assertContainsSubset($expected, $actual);
    }

    public function testAssertContainsSubsetWithNestedArrays(): void
    {
        $expected = [
            'id' => 1,
            'details' => [
                'city' => 'Paris',
            ],
        ];

        $actual = [
            'id' => 1,
            'name' => 'John',
            'details' => [
                'city' => 'Paris',
                'country' => 'France',
            ],
        ];

        static::assertContainsSubset($expected, $actual);
    }

    public function testAssertContainsSubsetFailsWithMissingKey(): void
    {
        $expected = ['id' => 1, 'name' => 'John', 'phone' => '123456'];
        $actual = ['id' => 1, 'name' => 'John'];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The key 'phone' does not exist in the current array");

        static::assertContainsSubset($expected, $actual);
    }

    public function testAssertDateTime(): void
    {
        static::assertDateTime('created_at', 'Y-m-d H:i:s', '2023-01-01 12:00:00');
    }

    public function testAssertDateTimeFailsWithInvalidFormat(): void
    {
        $this->expectException(ExpectationFailedException::class);

        static::assertDateTime('created_at', 'Y-m-d H:i:s', '01-01-2023 12:00:00');
    }

    public function testAssertDate(): void
    {
        static::assertDate('birth_date', null, '2023-01-01');
    }

    public function testAssertDateFailsWithInvalidFormat(): void
    {
        $this->expectException(ExpectationFailedException::class);

        static::assertDate('birth_date', null, '01/01/2023');
    }

    public function testAssertRegex(): void
    {
        static::assertRegex('code', '/^ABC[0-9]{3}$/', 'ABC123');
    }

    public function testAssertRegexFailsWithNonMatchingValue(): void
    {
        $this->expectException(ExpectationFailedException::class);

        static::assertRegex('code', '/^ABC[0-9]{3}$/', 'XYZ123');
    }

    public function testGetErrorMessageEntityNotFound(): void
    {
        $expected = \sprintf(ApiProblem::ENTITY_NOT_FOUND, 'entity');
        $this->assertSame($expected, static::getErrorMessageEntityNotFound());
    }

    public function testGetErrorMessageJwtNotFound(): void
    {
        $this->assertSame(ApiProblem::JWT_NOT_FOUND, static::getErrorMessageJwtNotFound());
    }

    public function testGetErrorMessageRestrictedAccess(): void
    {
        $this->assertSame(ApiProblem::RESTRICTED_ACCESS, static::getErrorMessageRestrictedAccess());
    }

    public function testExportValueWithScalar(): void
    {
        $result = $this->invokeMethod('exportValue', [123]);
        $this->assertSame('123', $result);
    }

    public function testExportValueWithArray(): void
    {
        $result = $this->invokeMethod('exportValue', [[1, 2, 3]]);
        $this->assertSame('array(3)', $result);
    }

    public function testExportValueWithObject(): void
    {
        $obj = new \stdClass();
        $result = $this->invokeMethod('exportValue', [$obj]);
        $this->assertSame('stdClass', $result);
    }

    /**
     * Helper method to invoke private methods.
     */
    private function invokeMethod($methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(\get_class($this));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this, $parameters);
    }
}
