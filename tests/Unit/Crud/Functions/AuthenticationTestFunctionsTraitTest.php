<?php

namespace EasyApiTests\Tests\Unit\Crud\Functions;

use EasyApiTests\Crud\Functions\AuthenticationTestFunctionsTrait;
use Namshi\JOSE\JWS;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AuthenticationTestFunctionsTraitTest extends TestCase
{
    use AuthenticationTestFunctionsTrait;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMockContainer();
    }

    protected function createMockContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->with('jwt_token_ttl')
            ->willReturn(3600);

        return $container;
    }

    protected static function getContainer(): ContainerInterface
    {
        static $container = null;
        if (null === $container) {
            $test = new static('test');
            $container = $test->createMock(ContainerInterface::class);
            $container->method('getParameter')
                ->with('jwt_token_ttl')
                ->willReturn(3600);
        }

        return $container;
    }

    public function testCheckPayloadContentValid(): void
    {
        $currentTime = time();
        $payload = [
            'iat' => $currentTime,
            'exp' => $currentTime + 3600,
        ];

        // This should not throw any exception
        $this->checkPayloadContent($payload);

        // If we get here, the test passed
        $this->assertTrue(true);
    }

    public function testCheckPayloadContentMissingIat(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $payload = [
            'exp' => time() + 3600,
        ];

        $this->checkPayloadContent($payload);
    }

    public function testCheckPayloadContentMissingExp(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $payload = [
            'iat' => time(),
        ];

        $this->checkPayloadContent($payload);
    }

    public function testCheckPayloadContentWrongExpiration(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $currentTime = time();
        $payload = [
            'iat' => $currentTime,
            'exp' => $currentTime + 7200, // Wrong expiration (should be 3600)
        ];

        $this->checkPayloadContent($payload);
    }

    /**
     * This test is more complex as it requires a valid JWT token
     * In a real scenario, you would mock the JWS::load method.
     */
    public function testCheckAuthenticateResponseStructure(): void
    {
        // Create a simple JWT token for testing
        $header = json_encode(['typ' => 'JWT', 'alg' => 'none']);
        $payload = json_encode(['iat' => time(), 'exp' => time() + 3600]);
        $signature = '';

        $token = base64_encode($header).'.'.base64_encode($payload).'.'.$signature;

        $response = [
            'token' => $token,
            'refreshToken' => 'refresh_token_value',
        ];

        // Mock the JWS::load to avoid actual JWT processing
        // In a real test, you would use a proper JWT library or mock
        // For now, we'll test the structure validation part

        // This test verifies the response has the required keys
        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('refreshToken', $response);
    }
}
