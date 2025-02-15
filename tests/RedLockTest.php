<?php

namespace Novon229\RedLock;

use Mockery;
use TestCase;
use Predis\Client as Redis;
use Illuminate\Support\Facades\App;

class RedLockTest extends TestCase
{
    private $servers = [
        [
            'host'     => 'host.test',
            'password' => 'password',
            'port'     => 6379,
            'database' => 0,
        ],
    ];

    public function testInstanciate()
    {
        self::expectNotToPerformAssertions();
        new RedLock([]);
    }

    private function assertGoodRedisMake($args)
    {
        $this->assertTrue(is_array($args));
        $app = app();
        if (method_exists($app, 'makeWith')) {
            // Laravel 5.4+
            $this->assertEquals($this->servers[0], $args['parameters']);
        } else {
            // Laravel 5.0 - 5.3
            $this->assertEquals($this->servers[0], $args[0]);
        }
    }

    public function testLock()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->once()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lock = $redlock->lock('XYZ', 300000);

        $this->assertGoodRedisMake($caught_args);
        $this->assertEquals('XYZ', $lock->getResource());
        $this->assertTrue(is_numeric($lock->getValidityTime()));
        $this->assertNotNull($lock->getToken());
    }

    public function testUnlock()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', '1234')
                ->once()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lock = new Lock($redlock, 300000, 'XYZ', '1234', 300000);
        $redlock->unlock($lock);

        $this->assertGoodRedisMake($caught_args);
    }

    public function testUnlockWithLockObject()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', '1234')
                ->once()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lock = new Lock($redlock, 300000, 'XYZ', '1234', 300000);
        $lock->unlock();

        $this->assertGoodRedisMake($caught_args);
    }

    public function testLockFail()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->times(3)
                ->andReturn(false);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', Mockery::any())
                ->times(3)
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lock = $redlock->lock('XYZ', 300000);

        $this->assertGoodRedisMake($caught_args);
        $this->assertNull($lock);
    }

    public function testUnlockFail()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', '1234')
                ->once()
                ->andReturn(false);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lock = new Lock($redlock, 300000, 'XYZ', '1234', 300000);
        $redlock->unlock($lock);

        $this->assertGoodRedisMake($caught_args);
    }

    public function testRefresh()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', '1234')
                ->once()
                ->andReturn(true);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->once()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $lockObj = new Lock($redlock, 300000, 'XYZ', '1234', 300000);
        $lock = $redlock->refreshLock($lockObj);

        $this->assertGoodRedisMake($caught_args);
        $this->assertEquals('XYZ', $lock->getResource());
        $this->assertTrue(is_numeric($lock->getValidityTime()));
        $this->assertNotNull($lock->getToken());
    }

    public function testRunLocked()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->once()
                ->andReturn(true);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', Mockery::any())
                ->once()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $results = $redlock->runLocked('XYZ', 300000, function () {
            return "ABC";
        });

        $this->assertGoodRedisMake($caught_args);
        $this->assertEquals('ABC', $results);
    }

    public function testRunLockedRefresh()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->twice()
                ->andReturn(true);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', Mockery::any())
                ->twice()
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $results = $redlock->runLocked('XYZ', 300000, function ($refresh) {
            $refresh();
            return "ABC";
        });

        $this->assertGoodRedisMake($caught_args);
        $this->assertEquals('ABC', $results);
    }

    public function testRunLockedRefreshFail()
    {
        $caught_args = null;
        App::bind(Redis::class, function ($app, $args) use (&$caught_args) {
            $caught_args = $args;
            $predis = Mockery::mock(Redis::class);
            $predis->shouldReceive('set')
                ->with('XYZ', Mockery::any(), "PX", 300000, "NX")
                ->times(4)
                ->andReturn(true, false, false, false);
            $predis->shouldReceive('eval')
                ->with(Mockery::any(), 1, 'XYZ', Mockery::any())
                ->times(4)
                ->andReturn(true);
            return $predis;
        });

        $redlock = new RedLock($this->servers);
        $results = $redlock->runLocked('XYZ', 300000, function ($refresh) {
            $refresh();
            return "ABC";
        });

        $this->assertGoodRedisMake($caught_args);
        $this->assertFalse($results);
    }
}
