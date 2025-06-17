<?php

namespace EasyApiTests\Crud;

use EasyApiTests\Crud\Functions\DescribeFormTestFunctionsTrait;

trait GetDescribeFormTestTrait
{
    use DescribeFormTestFunctionsTrait;

    protected static function initExecuteSetupOnAllTest(): void
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
