<?php

namespace EasyApiTests\src\Util\crud;

use EasyApiTests\src\Util\crud\functions\DescribeFormTestFunctionsTrait;

trait GetDescribeFormTestTrait
{
    use DescribeFormTestFunctionsTrait;

    protected static function initExecuteSetupOnAllTest()
    {
        static::$executeSetupOnAllTest = false;
    }


    /**
     * Nominal case for post form.
     */
    public function testForPost(): void
    {
        $this->doTestGetDescribeFormForPost();
    }

    /**
     * Nominal case for put form.
     */
    public function testForPut(): void
    {
        $this->doTestGetDescribeFormForPut();
    }

    /**
     * GET - Error case - 401 - Without authentication.
     */
    public function testGetWithoutAuthentication(): void
    {
        $this->doTestGetDescribeFormWithoutAuthentication();
    }

    /**
     * GET - Error case - 403 - Missing ADMIN role.
     */
    public function testGetWithoutRightC403(): void
    {
        $this->doTestGetDescribeFormWithoutRight();
    }
}
