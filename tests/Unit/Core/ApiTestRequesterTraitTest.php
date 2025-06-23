<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\ApiOutput;
use EasyApiTests\Core\ApiTestRequesterTrait;
use EasyApiTests\Core\CommandOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ApiTestRequesterTraitTest extends TestCase
{
    use ApiTestRequesterTrait;

    // Mock properties required by the trait
    protected static string $jwtTokenAuthorizationHeaderPrefix;
    protected static bool $useProfiler = false;
    protected static ?string $userIdentityProperty = null;
    protected static string $user = 'testuser';
    protected static bool $debug = false;
    protected static int $debugLevel = 0;
    public const DEBUG_LEVEL_ADVANCED = 2;
    protected static $client;
    protected static $router;

    // Mock objects for static methods
    private static $containerMock;
    private static $kernelMock;
    private static $mockCallCommand;

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset properties for testing
        static::$jwtTokenAuthorizationHeaderPrefix = 'Bearer';
        static::$useProfiler = false;
        static::$userIdentityProperty = null;
        static::$debug = false;

        // Mock client
        static::$client = $this->createMock(KernelBrowser::class);

        // Mock router
        static::$router = $this->createMock(RouterInterface::class);

        // Initialize static mocks
        self::initializeStaticMocks($this);
    }

    /**
     * Initialize static mocks using an instance of TestCase.
     */
    private static function initializeStaticMocks(TestCase $testCase)
    {
        // Container mock
        self::$containerMock = $testCase->createMock(Container::class);
        self::$containerMock->method('getParameter')
            ->willReturnMap([
                ['easy_api_tests.user_identity_property', 'username'],
                ['easy_api_tests.user_class', 'App\Entity\User'],
                ['kernel.environment', 'test'],
                ['kernel.project_dir', '/app'],
            ]);
        self::$containerMock->method('has')
            ->with('profiler')
            ->willReturn(true);

        // Configure get method to return a mock JWT token
        self::$containerMock->method('get')
            ->willReturnCallback(function ($service) use ($testCase) {
                if ('lexik_jwt_authentication.jwt_manager' === $service) {
                    // Create a simple stdClass with a create method instead of mocking JWTManager
                    $jwtManager = new class {
                        public function create()
                        {
                            return 'test.jwt.token';
                        }
                    };

                    return $jwtManager;
                }

                if ('security.user.provider.concrete.app_user_provider' === $service) {
                    // Return a simple user provider mock
                    return $testCase->createMock(\stdClass::class);
                }

                return null;
            });

        // Kernel mock
        self::$kernelMock = $testCase->createMock(KernelInterface::class);
    }

    /**
     * Required method for trait to work.
     */
    protected static function initializeCache(): void
    {
        // Mock implementation for testing
    }

    /**
     * Required method for trait to work.
     */
    protected static function logDebug($message): void
    {
        // Mock implementation for testing
    }

    /**
     * Required method for trait to work.
     */
    protected static function getContainer()
    {
        return self::$containerMock;
    }

    /**
     * Required method for trait to work.
     */
    protected static function getKernel()
    {
        return self::$kernelMock;
    }

    /**
     * Required method for trait to work.
     */
    protected static function get($service)
    {
        if ('lexik_jwt_authentication.jwt_manager' === $service) {
            // Create a simple stdClass with a create method instead of mocking JWTManager
            return new class {
                public function create()
                {
                    return 'test.jwt.token';
                }
            };
        }

        return null;
    }

    /**
     * Required method for trait to work.
     */
    protected static function getRepository($entityClass)
    {
        // Créer un objet simple qui répond à findOneBy
        return new class {
            public function findOneBy($criteria)
            {
                return new \stdClass();
            }
        };
    }

    /**
     * Required method for trait to work.
     */
    protected static function getDomainUrl(): string
    {
        return 'https://example.com';
    }

    /**
     * Test initializeRequester method.
     */
    public function testInitializeRequester(): void
    {
        // Save original value to restore later
        $originalPrefix = static::$jwtTokenAuthorizationHeaderPrefix;

        // Test initialization with default prefix
        static::initializeRequester();

        $this->assertSame('Bearer', static::$jwtTokenAuthorizationHeaderPrefix);

        // Test initialization with custom prefix
        static::$jwtTokenAuthorizationHeaderPrefix = 'JWT';
        static::initializeRequester();

        $this->assertSame('JWT', static::$jwtTokenAuthorizationHeaderPrefix);

        // Restore original value
        static::$jwtTokenAuthorizationHeaderPrefix = $originalPrefix;
    }

    /**
     * Test getAuthorizationTokenPrefix method.
     */
    public function testGetAuthorizationTokenPrefix(): void
    {
        static::$jwtTokenAuthorizationHeaderPrefix = 'JWT';

        $this->assertSame('JWT', static::getAuthorizationTokenPrefix());
    }

    /**
     * Test getAuthorizationStringFromToken method.
     */
    public function testGetAuthorizationStringFromToken(): void
    {
        static::$jwtTokenAuthorizationHeaderPrefix = 'Bearer';

        $token = 'sample.jwt.token';
        $expected = 'Bearer sample.jwt.token';

        $this->assertSame($expected, static::getAuthorizationStringFromToken($token));
    }

    /**
     * Test getUrl method with string route.
     */
    public function testGetUrlWithStringRoute(): void
    {
        $routeName = 'api_users_list';
        $expectedUrl = 'https://example.com/api/users';

        static::$router->method('generate')
            ->with($routeName, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        $result = static::getUrl($routeName);

        $this->assertSame($expectedUrl, $result);
    }

    /**
     * Test getUrl method with array route.
     */
    public function testGetUrlWithArrayRoute(): void
    {
        $route = [
            'name' => 'api_user_get',
            'params' => ['id' => 123],
        ];
        $expectedUrl = 'https://example.com/api/users/123';

        static::$router->method('generate')
            ->with('api_user_get', ['id' => 123], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        $result = static::getUrl($route);

        $this->assertSame($expectedUrl, $result);
    }

    /**
     * Test getUrl method with direct URL in array.
     */
    public function testGetUrlWithDirectUrlInArray(): void
    {
        $route = [
            'url' => 'https://example.com/custom/endpoint',
        ];

        $result = static::getUrl($route);

        $this->assertSame('https://example.com/custom/endpoint', $result);
    }

    /**
     * Test getUserIdentityProperty method.
     */
    public function testGetUserIdentityProperty(): void
    {
        // First call should initialize the property
        $this->assertSame('username', static::getUserIdentityProperty());

        // Change value and verify it's not overwritten
        static::$userIdentityProperty = 'email';
        $this->assertSame('email', static::getUserIdentityProperty());
    }

    /**
     * Test getUserClass method.
     */
    public function testGetUserClass(): void
    {
        $this->assertSame('App\Entity\User', static::getUserClass());
    }

    /**
     * Test getToken method.
     */
    public function testGetToken(): void
    {
        $token = static::getToken();
        $this->assertSame('test.jwt.token', $token);

        $token = static::getToken('admin');
        $this->assertSame('test.jwt.token', $token);
    }

    /**
     * Test executeRequest method.
     */
    public function testExecuteRequest(): void
    {
        // Set up mocks
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);

        $profile = $this->createMock(Profile::class);

        static::$client->method('getResponse')->willReturn($response);
        static::$client->method('getProfile')->willReturn($profile);

        $route = 'api_users_list';
        $expectedUrl = 'https://example.com/api/users';

        static::$router->method('generate')
            ->with($route, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        // Execute request
        $output = static::executeRequest('GET', $route);

        // Verify results
        $this->assertInstanceOf(ApiOutput::class, $output);
    }

    /**
     * Test HTTP method wrapper functions.
     */
    public function testHttpMethodWrappers(): void
    {
        // Set up mocks
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);

        $profile = $this->createMock(Profile::class);

        static::$client->method('getResponse')->willReturn($response);
        static::$client->method('getProfile')->willReturn($profile);

        $route = 'api_users_list';
        $expectedUrl = 'https://example.com/api/users';

        static::$router->method('generate')
            ->with($route, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        // Test HTTP GET
        $output = static::httpGet($route);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP POST
        $output = static::httpPost($route, ['name' => 'Test User']);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP PUT
        $output = static::httpPut($route, ['name' => 'Updated User']);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP DELETE
        $output = static::httpDelete($route);
        $this->assertInstanceOf(ApiOutput::class, $output);
    }

    /**
     * Test HTTP methods with login wrappers.
     */
    public function testHttpMethodsWithLogin(): void
    {
        // Set up mocks
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);

        $profile = $this->createMock(Profile::class);

        static::$client->method('getResponse')->willReturn($response);
        static::$client->method('getProfile')->willReturn($profile);

        $route = 'api_users_list';
        $expectedUrl = 'https://example.com/api/users';

        static::$router->method('generate')
            ->with($route, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        // Test HTTP GET with login
        $output = static::httpGetWithLogin($route);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP POST with login
        $output = static::httpPostWithLogin($route, null, ['name' => 'Test User']);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP PUT with login
        $output = static::httpPutWithLogin($route, null, ['name' => 'Updated User']);
        $this->assertInstanceOf(ApiOutput::class, $output);

        // Test HTTP DELETE with login
        $output = static::httpDeleteWithLogin($route);
        $this->assertInstanceOf(ApiOutput::class, $output);
    }

    /**
     * Test execCommand method.
     */
    public function testExecCommand(): void
    {
        // This would typically execute a real command, so we need to mock exec
        // For simplicity in this test, we'll verify the basic structure is correct

        $output = static::execCommand('test:command', ['--param' => 'value']);

        $this->assertInstanceOf(CommandOutput::class, $output);
    }

    /**
     * Test getProfilerLink method.
     */
    public function testGetProfilerLink(): void
    {
        // Mock ApiOutput
        $output = $this->createMock(ApiOutput::class);
        $headers = $this->createMock(ResponseHeaderBag::class);

        $output->method('getHeaders')
            ->willReturn($headers);

        // Test with debug disabled
        static::$debug = false;
        $result = static::getProfilerLink($output);
        $this->assertSame('', $result);

        // Test with debug enabled but no token
        static::$debug = true;
        $headers->method('get')
            ->with('x-debug-token')
            ->willReturn(null);

        $result = static::getProfilerLink($output);
        $this->assertSame('', $result);
    }
}
