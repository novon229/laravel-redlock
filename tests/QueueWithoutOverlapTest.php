<?php

namespace Novon229\RedLock\Traits;

use Novon229\RedLock\Facades\RedLock;
use Novon229\RedLock\Lock;
use Mockery;
use TestCase;

class QueueWithoutOverlapTest extends TestCase
{
    public function testInstanciate()
    {
        self::expectNotToPerformAssertions();
        new QueueWithoutOverlapJob();
    }

    public function testAllOfIt()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        $lock = new Lock(new \Novon229\RedLock\RedLock([]), 2, 'Novon229\RedLock\Traits\QueueWithoutOverlapJob::1000:', '1111', 2);

        RedLock::shouldReceive('lock')
            ->with("Novon229\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->twice()
            ->andReturn($lock);
        RedLock::shouldReceive('unlock')
            ->with($lock)
            ->twice()
            ->andReturn(true);

        $job->queue($queue, $job);

        $job->handle();

        $this->assertTrue($job->ran);
    }

    public function testFailToLock()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();

        RedLock::shouldReceive('lock')
            ->with("Novon229\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->once()
            ->andReturn(null);

        $id = $job->queue($queue, $job);

        $this->assertFalse($id);
    }

    public function testFailToRefresh()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        $lock = new Lock(new \Novon229\RedLock\RedLock([]), 2, 'Novon229\RedLock\Traits\QueueWithoutOverlapJob::1000:', '1111', 2);

        RedLock::shouldReceive('lock')
            ->with("Novon229\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->twice()
            ->andReturn(
                $lock,
                null
            );
        RedLock::shouldReceive('unlock')
            ->with($lock)
            ->once()
            ->andReturn(true);

        $job->queue($queue, $job);

        $this->expectException('Novon229\RedLock\Exceptions\QueueWithoutOverlapRefreshException');

        $job->handle();
    }

    public function testAllOfItDefaultLockTime()
    {
        $job = new QueueWithoutOverlapJobDefaultLockTime();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        $lock = new Lock(new \Novon229\RedLock\RedLock([]), 2, 'Novon229\RedLock\Traits\QueueWithoutOverlapJobDefaultLockTime::', '1111', 2);

        RedLock::shouldReceive('lock')
            ->with("Novon229\RedLock\Traits\QueueWithoutOverlapJobDefaultLockTime::", 300000)
            ->twice()
            ->andReturn($lock);
        RedLock::shouldReceive('unlock')
            ->with($lock)
            ->twice()
            ->andReturn(true);

        $job->queue($queue, $job);

        $job->handle();

        $this->assertTrue($job->ran);
    }
}
