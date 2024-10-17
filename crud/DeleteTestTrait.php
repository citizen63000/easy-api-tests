<?php

namespace EasyApiTests\crud;

use EasyApiTests\crud\functions\DeleteTestFunctionsTrait;

trait DeleteTestTrait
{
    use DeleteTestFunctionsTrait;

    protected static function initExecuteSetupOnAllTest(): void
    {
        static::$executeSetupOnAllTest = false;
    }


    /**
     * DELETE - Nominal case.
     */
    public function testDelete(): void
    {
        $this->doTestDelete();
    }

    /**
     * DELETE - Unexisting entity.
     */
    public function testDeleteNotFound(): void
    {
        $this->doTestDeleteNotFound(static::defaultEntityNotFoundId);
    }

    /**
     * DELETE - Error case - 401 - Without authentication.
     */
    public function testDeleteWithoutAuthentication(): void
    {
        $this->doTestDeleteWithoutAuthentication();
    }

    /**
     * DELETE - Error case - 403 - Missing right.
     */
    public function testDeleteWithoutRight403(): void
    {
        $this->doTestDeleteWithoutRight();
    }
}
