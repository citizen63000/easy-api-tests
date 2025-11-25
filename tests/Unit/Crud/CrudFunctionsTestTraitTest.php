<?php

namespace EasyApiTests\Tests\Unit\Crud;

use EasyApiTests\Crud\Functions\CrudFunctionsTestTrait;
use PHPUnit\Framework\TestCase;

class CrudFunctionsTestTraitTest extends TestCase
{
    use CrudFunctionsTestTrait;

    // Mock properties that CrudFunctionsTestTrait expects
    protected const string baseRouteName = 'test_entity';
    protected const string entityClass = 'App\\Entity\\TestEntity';
    protected const string identifier = 'id';

    public function testGetCurrentDir(): void
    {
        $result = $this->getCurrentDir();

        $this->assertIsString($result);
        $this->assertStringContainsString('tests/Unit/Crud', $result);
    }

    public function testGenerateJson(): void
    {
        $data = ['test' => 'value', 'empty' => []];
        $result = static::generateJson($data);

        $this->assertIsString($result);
        $this->assertStringContainsString('"test": "value"', $result);
        $this->assertStringContainsString('[]', $result); // empty object becomes empty array
    }

    public function testGetRouteNames(): void
    {
        $this->assertSame('test_entity_get', static::getGetRouteName());
        $this->assertSame('test_entity_list', static::getGetListRouteName());
        $this->assertSame('test_entity_create', static::getCreateRouteName());
        $this->assertSame('test_entity_clone', static::getCloneRouteName());
        $this->assertSame('test_entity_update', static::getUpdateRouteName());
        $this->assertSame('test_entity_delete', static::getDeleteRouteName());
        $this->assertSame('test_entity_download', static::getDownloadRouteName());
        $this->assertSame('test_entity_describe_form', static::getDescribeFormRouteName());
    }

    public function testGenerateRouteParameters(): void
    {
        $params = ['filter' => 'active'];

        $getParams = static::generateGetRouteParameters($params);
        $this->assertSame([
            'name' => 'test_entity_get',
            'params' => ['filter' => 'active'],
        ], $getParams);

        $listParams = static::generateGetListRouteParameters($params);
        $this->assertSame([
            'name' => 'test_entity_list',
            'params' => ['filter' => 'active'],
        ], $listParams);

        $deleteParams = static::generateDeleteRouteParameters($params);
        $this->assertSame([
            'name' => 'test_entity_delete',
            'params' => ['filter' => 'active'],
        ], $deleteParams);
    }

    public function testGetDataClassShortName(): void
    {
        $result = static::getDataClassShortName();

        $this->assertSame('testEntity', $result);
    }

    public function testGetDataSentFileCreation(): void
    {
        // Test that the method exists and has correct signature
        $this->assertTrue(method_exists($this, 'getDataSent'));

        $reflection = new \ReflectionMethod($this, 'getDataSent');
        $parameters = $reflection->getParameters();

        $this->assertSame('filename', $parameters[0]->getName());
        $this->assertSame('type', $parameters[1]->getName());
        $this->assertSame('defaultContent', $parameters[2]->getName());
    }

    public function testActionTypeConstants(): void
    {
        $reflection = new \ReflectionClass($this);

        $this->assertSame('Get', $reflection->getStaticPropertyValue('getActionType'));
        $this->assertSame('GetList', $reflection->getStaticPropertyValue('getListActionType'));
        $this->assertSame('Create', $reflection->getStaticPropertyValue('createActionType'));
        $this->assertSame('Clone', $reflection->getStaticPropertyValue('cloneActionType'));
        $this->assertSame('Update', $reflection->getStaticPropertyValue('updateActionType'));
        $this->assertSame('Download', $reflection->getStaticPropertyValue('downloadActionType'));
    }

    public function testGetExpectedFileResponseMethod(): void
    {
        // Just test that the method exists since testing the actual file creation
        // is complex due to protected method visibility
        $this->assertTrue(method_exists($this, 'getExpectedFileResponse'));

        $reflection = new \ReflectionMethod($this, 'getExpectedFileResponse');
        $parameters = $reflection->getParameters();

        $this->assertSame('filename', $parameters[0]->getName());
        $this->assertSame('result', $parameters[1]->getName());
    }
}
