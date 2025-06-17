<?php

namespace EasyApiTests\Crud;

use EasyApiTests\Crud\Functions\UpdateTestFunctionsTrait;

/**
 * testPutExistingEntity
 * testPutUnexistingEntity
 * testPutWithoutAuthentication
 * testPutWithoutRight
 */
trait UpdateTestTrait
{
    use UpdateTestFunctionsTrait;

    /**
     * PUT - Nominal case.
     */
    public function testPutExistingEntity(): void
    {
        $this->doTestUpdate(static::defaultEntityId, 'nominalCase.json');
    }

    /**
     * PUT - On unexisting entity case.
     */
    public function testPutUnexistingEntity(): void
    {
        $this->doTestUpdateNotFound(static::defaultEntityNotFoundId);
    }

    /**
     * PUT - Error case - 401 - Without authentication.
     */
    public function testPutWithoutAuthentication(): void
    {
        $this->doTestUpdateWithoutAuthentication();
    }

    /**
     * PUT - Error case - 403 - Missing update right.
     */
    public function testPutWithoutRight(): void
    {
        $this->doTestUpdateWithoutRight();
    }
}
