<?php

namespace EasyApiTests\Core;

use EasyApiCore\Util\ApiProblem;

trait ApiTestAssertionsTrait
{
    protected const string ERROR_MESSAGE_ENTITY_NOT_FOUND = ApiProblem::ENTITY_NOT_FOUND;
    protected const string ERROR_MESSAGE_JWT_NOT_FOUND = ApiProblem::JWT_NOT_FOUND;
    protected const string ERROR_MESSAGE_RESTRICTED_ACCESS = ApiProblem::RESTRICTED_ACCESS;

    protected static function getErrorMessageEntityNotFound(): string
    {
        return \sprintf(static::ERROR_MESSAGE_ENTITY_NOT_FOUND, 'entity');
    }

    protected static function getErrorMessageJwtNotFound(): string
    {
        return static::ERROR_MESSAGE_JWT_NOT_FOUND;
    }

    protected static function getErrorMessageRestrictedAccess(): string
    {
        return static::ERROR_MESSAGE_RESTRICTED_ACCESS;
    }

    /**
     * Use it to add personal assessable function, call it in setUp().
     */
    protected function addAdditionalAssessableFunction(string $functionName): void
    {
        static::$additionalAssessableFunctions[] = $functionName;
    }

    /**
     * Determine if two arrays are similar.
     */
    protected static function assertArraysAreSimilar(array $a, array $b): void
    {
        sort($a);
        sort($b);

        static::assertEquals($a, $b);
    }

    /**
     * Determine if two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     */
    protected static function assertAssociativeArraysAreSimilar(array $a, array $b): void
    {
        // Indexes must match
        static::assertCount(\count($a), $b, 'The array have not the same size');

        // Compare values
        foreach ($a as $k => $v) {
            static::assertTrue(isset($b[$k]), "The second array have not the key '{$k}'");
            static::assertEquals($v, $b[$k], "Values for '{$k}' key do not match");
        }
    }

    /**
     * Asserts an API problem standard error.
     */
    protected static function assertApiProblemError(ApiOutput $apiOutput, int $expectedStatus, array $messages): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        $error = $apiOutput->getData();
        static::assertArrayHasKey('errors', $error);
        array_walk($messages, static function (&$message) {
            $message = static::$errorPrefix.$message;
        });
        static::assertArraysAreSimilar($messages, $error['errors']);
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param array     $data           Expected data (only field or with values
     * @param bool      $onlyFields     Check only fields (check values instead)
     */
    protected static function assertApiEntityResult(ApiOutput $apiOutput, int $expectedStatus, array $data, $onlyFields = true): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        if (true === $onlyFields) {
            static::assertFields($data, $apiOutput->getData());
        } else {
            static::assertAssociativeArraysAreSimilar($data, $apiOutput->getData());
        }
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param int       $count          List count
     * @param array     $fields         Expected fields
     */
    protected static function assertApiEntityListResult(ApiOutput $apiOutput, int $expectedStatus, int $count, ?int $total, array $fields, bool $assertOnlyFields = false): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        $data = $apiOutput->getData();
        static::assertCount($count, $data, "Expected list size : {$count}, get ".\count($data));
        if (null !== $total) {
            static::assertEquals($total, $apiOutput->getHeaderLine('X-Total-Results'));
        }
        foreach ($data as $entity) {
            static::assertFields($fields, $entity, $assertOnlyFields);
        }
    }

    /**
     * Asserts that entity contains exactly these fields.
     *
     * @param array      $fields Expected fields
     * @param array|null $entity JSON entity as array
     */
    protected static function assertFields(array $fields, ?array $entity = null, bool $assertOnlyFields = false, bool $atLeast = false): void
    {
        if (!$assertOnlyFields) {
            static::assertNotNull($entity, 'The entity should not be null !');
            if (!$atLeast) {
                sort($fields);
                ksort($entity);
                static::assertCount(
                    \count($fields),
                    $entity,
                    \sprintf(
                        "Expected field count : %d, get %d\nFields expected: %s\nFields received: %s\nDiff: %s",
                        \count($fields),
                        \count($entity),
                        implode(', ', $fields),
                        implode(', ', array_keys($entity)),
                        implode(', ', array_merge(array_diff($fields, array_intersect($fields, array_keys($entity))), array_diff(array_keys($entity), array_intersect($fields, array_keys($entity)))))
                    )
                );
            }
        }
        foreach ($fields as $field) {
            static::assertArrayHasKey($field, $entity, "Entity must have this field : {$field}");
        }
    }

    /**
     * Asserts that array $expected is the same as $result using assertions methods in expected result.
     */
    protected static function assertAssessableContent(array &$expected, array &$result): void
    {
        $assessableFunctions = array_merge(static::assessableFunctions, static::$additionalAssessableFunctions);
        foreach ($expected as $key => $value) {
            if (\array_key_exists($key, $result)) {
                if (!\is_array($value)) {
                    if (preg_match('/^\\\\|^{/', $value)) {
                        foreach ($assessableFunctions as $functionName) {
                            $functionExpr1 = "\\\\{$functionName}\((.*)\)";
                            $functionExpr2 = "{{$functionName}\((.*)\)}";
                            if (preg_match("/{$functionExpr1}|$functionExpr2/", $value, $matches)) {
                                static::$functionName($key, !empty($matches[1]) ? self::getAssessableFunctionParameter($matches[1]) : null, $result[$key]);
                                unset($expected[$key]);
                                unset($result[$key]);
                                break;
                            }
                        }
                    }
                } elseif (\is_array($result[$key])) {
                    static::assertAssessableContent($expected[$key], $result[$key]);
                }
            }
        }
    }

    /**
     * @param string|null $param
     *
     * @return false|string|null
     */
    private static function getAssessableFunctionParameter(string $param)
    {
        // value in quotes
        if ('\'' === mb_substr($param, 0, 1) && '\'' === mb_substr($param, mb_strlen($param) - 1, 1)) {
            return mb_substr($param, 1, mb_strlen($param) - 2);
        }

        return $param;
    }

    /**
     * Test if the value is DateTime
     * usage : assertDateTime([format for ex 'y-m-d']).
     */
    protected static function assertDateTime($key, $format, $value): void
    {
        $expectedFormat = $format ?? static::getContainer()->getParameter('easy_api_tests.datetime_format');
        $errorMessage = "Invalid date format for {$key} field: expected format {$expectedFormat}, get value '{$value}'";
        static::assertTrue(!empty($value), $errorMessage);
        $date = \DateTime::createFromFormat($expectedFormat, $value);
        static::assertNotFalse($date, "assertDateTimeNow : invalid format $expectedFormat for key $key (value $value)");
        static::assertTrue($date && (string) ($date->format($expectedFormat) === (string) $value), $errorMessage);
    }

    /**
     * Test if the value is DateTime and value is now with 1 second range
     * usage : assertDateTimeNow([format for ex 'y-m-d']).
     */
    protected static function assertDateTimeNow(string $key, ?string $format, ?string $value)
    {
        $expectedFormat = $format ?? static::getContainer()->getParameter('easy_api_tests.datetime_format');
        $errorMessage = "Invalid date format for {$key} field: expected format {$expectedFormat}, get value '{$value}'";
        static::assertTrue(!empty($value), $errorMessage);
        $date = \DateTime::createFromFormat($expectedFormat, $value);
        static::assertNotFalse($date, "assertDateTimeNow : invalid format $expectedFormat for key $key (value $value)");
        static::assertTrue($date->diff(new \DateTime())->format('%S') <= 1, $errorMessage);
    }

    /**
     * Test if the value is Date (format Y-m-d)
     * usage : assertDate().
     */
    protected static function assertDate($key, $expected, $value): void
    {
        static::assertDateTime($key, 'Y-m-d', $value);
    }

    /**
     * Test if the value is file url
     * You can use {UID} & {UUID} tags
     * Usage : assertFileUrl(my_directory_{UUID}/file_{UID}.jpg).
     */
    private static function assertFileUrl($key, $expected, $value): void
    {
        $expected = str_replace('{uri_prefix}', static::getDomainUrl(), $expected);
        $expected = str_replace(['.', '/', '-'], ['\.', '\/', '\-'], $expected);
        $expectedUUID = '[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+';
        $expected = str_replace('{UUID}', $expectedUUID, $expected);
        $expected = str_replace('{UID}', '[a-zA-Z0-9]+', $expected);
        $expected = "/$expected/";
        $errorMessage = "Invalid file url in {$key} field: expected {$expected}, get value {$value}.";
        static::assertMatchesRegularExpression($expected, $value, $errorMessage);
    }

    /**
     * Test if the value is filename with extension
     * You can use {UID} & {UUID} tags
     * Usage : assertFileUrl(my_file_{UID}.jpg).
     */
    private static function assertFileName($key, $expected, $value): void
    {
        $expected = str_replace(['.', '-'], ['\.', '\-'], $expected);
        $expected = str_replace('{UUID}', static::regexp_uuid, $expected);
        $expected = str_replace('{UID}', static::regexp_uid, $expected);
        $expected = "/$expected/";
        $errorMessage = "Invalid file name in {$key} field: expected {$expected}, get value {$value}.";
        static::assertMatchesRegularExpression($expected, $value, $errorMessage);
    }

    /**
     * Test if the value is UUID
     * Usage : assertUUID().
     */
    private static function assertUUID($key, $expected, $value): void
    {
        $expected = static::regexp_uuid;
        $errorMessage = "Invalid UUID in {$key} field: expected {$expected}, get value {$value}";
        static::assertMatchesRegularExpression("/$expected/", $value, $errorMessage);
    }

    /**
     * Test if the value has regex
     * usage : assertRegex([regex for ex 'toto[0-9]{3}']).
     */
    protected static function assertRegex($key, $regex, $value): void
    {
        static::assertMatchesRegularExpression($regex, $value, "{$key} field does not match regex {$regex}, get value '{$value}'");
    }

    /**
     * Checks that $actual contains at least all fields from $expected with the same values recursively.
     *
     * @param array  $expected Expected data (subset)
     * @param array  $actual   Data to check
     * @param string $path     Current path for error messages (used internally for recursion)
     * @param string $message  Error message
     */
    protected static function assertArrayContainsSubset(array $expected, array $actual, string $path = '', string $message = ''): void
    {
        foreach ($expected as $key => $expectedValue) {
            $currentPath = $path ? "$path.$key" : $key;

            static::assertArrayHasKey(
                $key,
                $actual,
                $message ?: "The key '$currentPath' does not exist in the current array"
            );

            if (\is_array($expectedValue)) {
                static::assertIsArray(
                    $actual[$key],
                    $message ?: "The value at '$currentPath' should be an array"
                );

                static::assertArrayContainsSubset(
                    $expectedValue,
                    $actual[$key],
                    $currentPath,
                    $message
                );
            } elseif (\is_object($expectedValue) && \is_object($actual[$key])) {
                // Generic object handling - compare accessible properties
                static::assertEquals(
                    (array) $expectedValue,
                    (array) $actual[$key],
                    $message ?: "The object at '$currentPath' does not match"
                );
            } else {
                // Simple value check for scalar types
                static::assertEquals(
                    $expectedValue,
                    $actual[$key],
                    $message ?: "The value at '$currentPath' does not match. Expected: ".
                        self::exportValue($expectedValue).', Got: '.self::exportValue($actual[$key])
                );
            }
        }
    }

    /**
     * Converts a value to a readable string for error messages.
     */
    private static function exportValue(mixed $value): string
    {
        if (\is_scalar($value) || null === $value) {
            return var_export($value, true);
        }

        if (\is_array($value)) {
            return 'array('.\count($value).')';
        }

        if (\is_object($value)) {
            return \get_class($value);
        }

        return \gettype($value);
    }

    /**
     * Public method to be used in tests.
     */
    public static function assertContainsSubset(array $expected, array $actual, string $message = ''): void
    {
        static::assertArrayContainsSubset($expected, $actual, '', $message);
    }
}
