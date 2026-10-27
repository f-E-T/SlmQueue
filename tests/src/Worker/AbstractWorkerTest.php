<?php

namespace SlmQueueTest\Worker;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\ResponseCollection;
use PHPUnit\Framework\TestCase;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\MaxRunsStrategy;
use SlmQueue\Worker\AbstractWorker;
use SlmQueue\Worker\Event\BootstrapEvent;
use SlmQueue\Worker\Event\FinishEvent;
use SlmQueue\Worker\Event\ProcessQueueEvent;
use SlmQueue\Worker\Event\ProcessStateEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueue\Worker\Result\ExitWorkerLoopResult;
use SlmQueue\Worker\Result\ProcessStateResult;
use SlmQueue\Worker\WorkerInterface;
use SlmQueueTest\Asset\SimpleWorker;

class AbstractWorkerTest extends TestCase
{
    /** @var SimpleWorker */
    protected $worker;
    protected $queue;
    protected $job;
    protected $maxRuns;

    public function setUp(): void
    {
        $this->worker = new SimpleWorker();
        $this->queue = $this->createStub(QueueInterface::class);
        $this->job = $this->createStub(JobInterface::class);

        // set max runs so our tests won't run forever
        $this->maxRuns = new MaxRunsStrategy();
        $this->maxRuns->setMaxRuns(1);
        $this->maxRuns->attach($this->worker->getEventManager());
    }

    public function testCorrectIdentifiersAreSetToEventManager(): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->worker->getEventManager();

        static::assertTrue(in_array(AbstractWorker::class, $eventManager->getIdentifiers()));
        static::assertTrue(in_array(SimpleWorker::class, $eventManager->getIdentifiers()));
        static::assertTrue(in_array(WorkerInterface::class, $eventManager->getIdentifiers()));
    }

    public function testWorkerLoopEvents(): void
    {
        $eventManager = $this->createMock(EventManager::class);
        $this->worker = new SimpleWorker($eventManager);

        $triggered = [];

        // BootstrapEvent, FinishEvent and ProcessStateEvent
        $stateResponse = new ResponseCollection();
        $stateResponse->push(ProcessStateResult::withState('some strategy state'));
        $stateResponse->push(ProcessStateResult::withState('another strategy state'));
        $eventManager->expects($this->exactly(3))->method('triggerEvent')->willReturnCallback(
            function (WorkerEventInterface $event) use (&$triggered, $stateResponse) {
                $triggered[] = $event;

                return $event instanceof ProcessStateEvent ? $stateResponse : new ResponseCollection();
            }
        );

        // first ProcessQueueEvent with no exit, second one with exit
        $continueResponse = new ResponseCollection();
        $continueResponse->push(null);
        $exitResponse = new ResponseCollection();
        $exitResponse->push(ExitWorkerLoopResult::withReason('some exit reason'));
        $exitResponse->setStopped(true);
        $untilResponses = [$continueResponse, $exitResponse];
        $eventManager->expects($this->exactly(2))->method('triggerEventUntil')->willReturnCallback(
            function (callable $callback, WorkerEventInterface $event) use (&$triggered, &$untilResponses) {
                $triggered[] = $event;

                return array_shift($untilResponses);
            }
        );

        $result = $this->worker->processQueue($this->queue);

        static::assertEquals([
            new BootstrapEvent($this->worker, $this->queue),
            new ProcessQueueEvent($this->worker, $this->queue),
            new ProcessQueueEvent($this->worker, $this->queue),
            new FinishEvent($this->worker, $this->queue),
            new ProcessStateEvent($this->worker),
        ], $triggered);
        static::assertEquals(["some strategy state", "another strategy state", "some exit reason"], $result);
    }

    public function testProcessQueueSetsOptionsOnProcessQueueEvent(): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->worker->getEventManager();

        $options = ['foo' => 'bar'];

        $eventManager->attach(
            WorkerEventInterface::EVENT_PROCESS_QUEUE,
            function (ProcessQueueEvent $e) use ($options) {
                static::assertEquals($options, $e->getOptions());
            }
        );

        $this->worker->processQueue($this->queue, $options);
    }
}
