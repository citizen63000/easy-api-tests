<?php

namespace EasyApiTests\Crud;

use EasyApiTests\Crud\Functions\CloneTestFunctionsTrait;

trait CloneTestTrait
{
    use CloneTestFunctionsTrait;

    /**
     * POST - Clone with all fields.
     */
    public function testClone(): void
    {
        $this->doTestClone();
    }

    /**
     * POST - Error case - 401 - Without authentication.
     */
    public function testCloneWithoutAuthentication(): void
    {
        $this->doTestCloneWithoutAuthentication();
    }

    /**
     * POST - Error case - 403 - Missing rights.
     */
    public function testCloneWithoutRights(): void
    {
        $this->doTestCloneWithoutRight();
    }
}
