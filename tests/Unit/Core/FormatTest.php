<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\Format;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class FormatTest extends TestCase
{
    public function testGetEncoderJson(): void
    {
        $encoder = Format::getEncoder(Format::JSON);
        $this->assertInstanceOf(JsonEncoder::class, $encoder);
    }

    public function testGetEncoderXml(): void
    {
        $encoder = Format::getEncoder(Format::XML);
        $this->assertInstanceOf(XmlEncoder::class, $encoder);
    }

    public function testGetEncoderInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format "invalid/format" unrecognized');

        Format::getEncoder('invalid/format');
    }

    public function testWriteDataJson(): void
    {
        $data = ['test' => true, 'value' => 123];
        $result = Format::writeData($data, Format::JSON);

        $this->assertSame('{"test":true,"value":123}', $result);
    }

    public function testWriteDataXml(): void
    {
        $data = ['test' => true, 'value' => 123];
        $result = Format::writeData($data, Format::XML);

        $this->assertStringContainsString('<test>1</test>', $result);
        $this->assertStringContainsString('<value>123</value>', $result);
    }

    public function testReadDataJson(): void
    {
        $json = '{"test":true,"value":123}';
        $result = Format::readData($json, Format::JSON);

        $this->assertSame(['test' => true, 'value' => 123], $result);
    }

    public function testReadDataXml(): void
    {
        $xml = '<?xml version="1.0"?><root><test>true</test><value>123</value></root>';
        $result = Format::readData($xml, Format::XML);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test', $result);
        $this->assertArrayHasKey('value', $result);
    }

    public function testFormatConstants(): void
    {
        $this->assertSame('application/json', Format::JSON);
        $this->assertSame('application/xml', Format::XML);
    }

    public function testEncoderFormats(): void
    {
        $reflection = new \ReflectionClass(Format::class);
        $encoderFormats = $reflection->getStaticPropertyValue('encoderFormats');

        $this->assertArrayHasKey(Format::JSON, $encoderFormats);
        $this->assertArrayHasKey(Format::XML, $encoderFormats);
        $this->assertSame(JsonEncoder::FORMAT, $encoderFormats[Format::JSON]);
        $this->assertSame('xml', $encoderFormats[Format::XML]);
    }
}
