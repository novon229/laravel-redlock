<?php

namespace Novon229\RedLock\Facades;

use Mockery;
use TestCase;
use Predis\Client as Redis;
use Illuminate\Support\Facades\App;

class RedLockFacadeTest extends TestCase
{
    public function testAccess()
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('doodad')->once();
        App::instance('redlock', $mock);

        RedLock::doodad();
    }

    public function testRoot()
    {
        $this->assertTrue(RedLock::getFacadeRoot() instanceof \Novon229\RedLock\RedLock);
    }
}
