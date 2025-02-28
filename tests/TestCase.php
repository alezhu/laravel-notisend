<?php

namespace Alezhu\LaravelNotisend\Tests;

use Alezhu\PHPUnitArrayContainsAsserts\ArrayContainsTrait;
use Illuminate\Support\Facades\Facade;
use Mockery;

class TestCase extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    use ArrayContainsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
    }
}