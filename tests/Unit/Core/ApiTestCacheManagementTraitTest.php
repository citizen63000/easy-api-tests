<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\ApiTestCacheManagementTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;

class ApiTestCacheManagementTraitTest extends TestCase
{
    use ApiTestCacheManagementTrait;

    private static Container $containerMock;
    private static ArrayAdapter $cacheAdapter;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static properties between tests
        static::$useCache = true;
        static::$cache = null;

        // Create cache adapter for testing
        self::$cacheAdapter = new ArrayAdapter();

        // Create a mock for Container
        self::$containerMock = $this->createMock(Container::class);
        self::$containerMock->method('get')
            ->with('cache.app')
            ->willReturn(self::$cacheAdapter);
    }

    protected static function getContainer(): Container
    {
        return self::$containerMock;
    }

    public function testInitializeCache(): void
    {
        // Verify cache is null at the beginning
        $this->assertNull(static::$cache);

        // Initialize the cache
        static::initializeCache();

        // Verify the cache is now configured
        $this->assertSame(self::$cacheAdapter, static::$cache);

        // Verify that initializing an already configured cache does not change anything
        $previousCache = static::$cache;
        static::initializeCache();
        $this->assertSame($previousCache, static::$cache);
    }

    public function testGetCachedDataWithNormalKey(): void
    {
        // Initialize the cache
        static::initializeCache();

        // Set up a cache entry
        $key = 'test_key';
        $item = self::$cacheAdapter->getItem($key);
        $item->set('test_value');
        self::$cacheAdapter->save($item);

        // Retrieve the item from cache
        $result = static::getCachedData($key);

        // Verify the item was correctly retrieved
        $this->assertNotNull($result);
        if (null !== $result) {
            $this->assertSame('test_value', $result->get());
        }
    }

    public function testGetCachedDataWithSpecialCharactersInKey(): void
    {
        // Initialize the cache
        static::initializeCache();

        // Key with special characters
        $originalKey = 'test/{key}@with(special)\\chars';
        $escapedKey = str_replace(['{', '}', '(', ')', '/', '\\', '@'], '_ESCAPED_', $originalKey);

        // Set up a cache entry with the escaped key
        $item = self::$cacheAdapter->getItem($escapedKey);
        $item->set('special_value');
        self::$cacheAdapter->save($item);

        // Retrieve the item from cache with the original key
        $result = static::getCachedData($originalKey);

        // Verify the item was correctly retrieved
        $this->assertNotNull($result);
        if (null !== $result) {
            $this->assertSame('special_value', $result->get());
        }
    }

    public function testGetCachedDataHandlesExceptions(): void
    {
        // Initialize the cache first
        static::initializeCache();

        // Create a cache that will throw an exception when getItem is called
        $exceptionThrowingCache = new class extends ArrayAdapter {
            public function getItem($key): never
            {
                throw new \Exception('Cache error');
            }
        };

        // Replace the cache with our exception-throwing version
        static::$cache = $exceptionThrowingCache;

        // Verify that getCachedData returns null when an exception occurs
        $result = static::getCachedData('any_key');
        $this->assertNull($result);
    }

    public function testGetCachedDataWithNullCache(): void
    {
        // Ensure cache is null
        static::$cache = null;

        // Test should fail because initializeCache wasn't called
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to a member function getItem() on null');

        static::getCachedData('test_key');
    }

    public function testUseCacheProperty(): void
    {
        // Verify default value
        $this->assertTrue(static::$useCache);

        // Change the value
        static::$useCache = false;
        $this->assertFalse(static::$useCache);
    }
}
