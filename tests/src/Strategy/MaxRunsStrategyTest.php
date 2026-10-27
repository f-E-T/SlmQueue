<?php

namespace SlmQueueTest\Listener\Strategy;

use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Strategy\MaxRunsStrategy;
use SlmQueue\Worker\Event\ProcessQueueEvent;
use SlmQueue\Worker\Event\ProcessStateEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueue\Worker\Result\ExitWorkerLoopResult;
use SlmQueueTest\Asset\SimpleWorker;
use SlmQueueTest\Util\ListenerAttachmentAssertions;

class MaxRunsStrategyTest extends TestCase
{
    use ListenerAttachmentAssertions;

    protected $queue;
    protected $worker;
    /** @var MaxRunsStrategy */
    protected $listener;

    public function setUp(): void
    {
        $this->queue = $this->createStub(QueueInterface::class);
        $this->worker = new SimpleWorker();
        $this->listener = new MaxRunsStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy(): void
    {
        static::assertInstanceOf(AbstractStrategy::class, $this->listener);
    }

    public function testMaxRunsDefault(): void
    {
        static::assertEquals(0, $this->listener->getMaxRuns());
    }

    public function testMaxRunsSetter(): void
    {
        $this->listener->setMaxRuns(2);

        static::assertEquals(2, $this->listener->getMaxRuns());
    }

    public function testListensToCorrectEventAtCorrectPriority(): void
    {
        $priority = 1;

        $this->assertListenerAttachments($this->listener, $priority, [
            [WorkerEventInterface::EVENT_PROCESS_QUEUE, [$this->listener, 'onStopConditionCheck'], -1000],
            [WorkerEventInterface::EVENT_PROCESS_STATE, [$this->listener, 'onReportQueueState'], 1],
        ]);
    }

    public function testOnStopConditionCheckHandler(): void
    {
        $this->listener->setMaxRuns(3);

        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNull($result);

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertStringContainsString('1 jobs processed', $stateResult->getState());

        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNull($result);

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertStringContainsString('2 jobs processed', $stateResult->getState());

        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNotNull($result);
        static::assertInstanceOf(ExitWorkerLoopResult::class, $result);
        static::assertStringContainsString('maximum of 3 jobs processed', $result->getReason());

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertStringContainsString('3 jobs processed', $stateResult->getState());
    }
}
