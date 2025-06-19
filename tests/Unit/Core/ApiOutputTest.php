<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\ApiOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ApiOutputTest extends TestCase
{
    public function testConstructorWithoutFormat(): void
    {
        $response = new Response('{"test":true}', 200);
        $apiOutput = new ApiOutput($response);

        $this->assertSame($response, $apiOutput->getResponse());
        $this->assertSame(200, $apiOutput->getStatusCode());
        $this->assertNull($apiOutput->getProfile());
    }

    public function testConstructorWithJsonFormat(): void
    {
        $content = '{"test":true,"name":"example"}';
        $response = new Response($content, 200);
        $apiOutput = new ApiOutput($response, 'application/json');

        $this->assertSame(['test' => true, 'name' => 'example'], $apiOutput->getData());
        $this->assertSame($content, $apiOutput->getData(true));
    }

    public function testConstructorWithXmlFormat(): void
    {
        $content = '<?xml version="1.0"?><root><test>true</test></root>';
        $response = new Response($content, 200);
        $apiOutput = new ApiOutput($response, 'application/xml');

        $this->assertIsArray($apiOutput->getData());
    }

    public function testConstructorWithInvalidJson(): void
    {
        $content = '{"invalid":json}';
        $response = new Response($content, 200);
        $apiOutput = new ApiOutput($response, 'application/json');

        $this->assertIsString($apiOutput->getData());
        $this->assertStringContainsString('Syntax error', $apiOutput->getData());
    }

    public function testConstructorWithProfile(): void
    {
        $response = new Response('test', 200);
        $profile = $this->createMock(Profile::class);
        $apiOutput = new ApiOutput($response, null, $profile);

        $this->assertSame($profile, $apiOutput->getProfile());
    }

    public function testMagicCallMethod(): void
    {
        $response = new Response('test', 201);
        $apiOutput = new ApiOutput($response);

        $this->assertSame(201, $apiOutput->getStatusCode());
        // Test that magic call works - just verify it returns something
        $this->assertNotNull($apiOutput->getContent());
        $this->assertSame('test', $apiOutput->getContent());
    }

    public function testGetHeaders(): void
    {
        $response = new Response('test', 200);
        $response->headers->set('Content-Type', 'application/json');
        $apiOutput = new ApiOutput($response);

        $headers = $apiOutput->getHeaders();
        $this->assertNotNull($headers);
        $this->assertSame('application/json', $headers->get('Content-Type'));
    }

    public function testGetHeaderLine(): void
    {
        $response = new Response('test', 200);
        $response->headers->set('Authorization', 'Bearer token123');
        $apiOutput = new ApiOutput($response);

        $this->assertSame('Bearer token123', $apiOutput->getHeaderLine('Authorization'));
        $this->assertNull($apiOutput->getHeaderLine('NonExistent'));
    }

    public function testGetHeaderLineWithNonExistentHeader(): void
    {
        $response = new Response('test', 200);
        $apiOutput = new ApiOutput($response);

        $this->assertNull($apiOutput->getHeaderLine('NonExistent'));
    }
}
