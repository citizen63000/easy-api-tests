<?php

namespace EasyApiTests\Crud\Functions;

use Symfony\Component\HttpFoundation\Response;

trait Generic401TestTrait
{
    /**
     * GET - Error case - 401 - Without authentication.
     */
    public function testGet401(): void
    {
        $apiOutput = self::httpGet(['name' => static::routeName, 'params' => []], false);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $apiOutput->getStatusCode());
    }
}
