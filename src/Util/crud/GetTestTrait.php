<?php

namespace EasyApiTests\src\Util\crud;

use EasyApiTests\src\Util\crud\functions\GetTestFunctionsTrait;

/**
 * testGet()
 * testGetNotFound()
 * testGetWithoutAuthentication()
 * testGetWithoutRight()
 */
trait GetTestTrait
{
    use GetTestFunctionsTrait;

    protected static function initExecuteSetupOnAllTest()
    {
        static::$executeSetupOnAllTest = false;
    }

    /**
     * GET - Nominal case.
     */
    public function testGet(): void
    {
        $this->doTestGet(static::defaultEntityId);
    }

    /**
     * GET - Unexisting entity.
     */
    public function testGetNotFound(): void
    {
        $this->doTestGetNotFound();
    }

    /**
     * GET - Error case - 401 - Without authentication.
     */
    public function testGetWithoutAuthentication(): void
    {
        $this->doTestGetWithoutAuthentication();
    }

    /**
     * GET - Error case - 403 - Missing read right.
     */
    public function testGetWithoutRight(): void
    {
        $this->doTestGetWithoutRight();
    }
}
