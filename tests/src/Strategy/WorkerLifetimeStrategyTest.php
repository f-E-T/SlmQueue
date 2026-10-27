<?php

namespace SlmQueueTest\Listener\Strategy;

use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Strategy\WorkerLifetimeStrategy;
use SlmQueue\Worker\Event\BootstrapEvent;
use SlmQueue\Worker\Event\ProcessQueueEvent;
use SlmQueue\Worker\Event\ProcessStateEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueue\Worker\Result\ExitWorkerLoopResult;
use SlmQueueTest\Asset\SimpleWorker;
use SlmQueueTest\Util\ListenerAttachmentAssertions;

class WorkerLifetimeStrategyTest extends TestCase
{
    use ListenerAttachmentAssertions;

    protected $queue;
    protected $worker;

    /**
     * @var WorkerLifetimeStrategy
     */
    protected $listener;

    public function setUp(): void
    {
        $this->queue = $this->createStub(QueueInterface::class);
        $this->worker = new SimpleWorker();
        $this->listener = new WorkerLifetimeStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy(): void
    {
        static::assertInstanceOf(AbstractStrategy::class, $this->listener);
    }

    public function testLifetimeDefault(): void
    {
        static::assertEquals(3600, $this->listener->getLifetime());
    }

    public function testLifetimeSetter(): void
    {
        $this->listener->setLifetime(7200);

        static::assertEquals(7200, $this->listener->getLifetime());
    }

    public function testListensToCorrectEventAtCorrectPriority(): void
    {
        $priority = 1;

        $this->assertListenerAttachments($this->listener, $priority, [
            [WorkerEventInterface::EVENT_BOOTSTRAP, [$this->listener, 'onBootstrap'], 1],
            [WorkerEventInterface::EVENT_PROCESS_QUEUE, [$this->listener, 'checkRuntime'], -1000],
            [WorkerEventInterface::EVENT_PROCESS_IDLE, [$this->listener, 'checkRuntime'], -1000],
            [WorkerEventInterface::EVENT_PROCESS_STATE, [$this->listener, 'onReportQueueState'], 1],
        ]);
    }

    public function testOnStopConditionCheckHandler(): void
    {
        $this->listener->setLifetime(2);

        $this->listener->onBootstrap(new BootstrapEvent($this->worker, $this->queue));

        $result = $this->listener->checkRuntime(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNull($result);

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertStringContainsString(' seconds passed', $stateResult->getState());

        sleep(3);

        $result = $this->listener->checkRuntime(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNotNull($result);
        static::assertInstanceOf(ExitWorkerLoopResult::class, $result);
        static::assertStringContainsString('lifetime of 2 seconds reached', $result->getReason());

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertStringContainsString('3 seconds passed', $stateResult->getState());
    }
}
