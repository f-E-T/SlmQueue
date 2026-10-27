<?php

namespace SlmQueueTest\Strategy;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\ListenerAggregateInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Strategy\AttachQueueListenersStrategy;
use SlmQueue\Strategy\StrategyPluginManager;
use SlmQueue\Worker\Event\BootstrapEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueueTest\Asset\SimpleStrategy;
use SlmQueueTest\Asset\SimpleWorker;
use SlmQueueTest\Util\ListenerAttachmentAssertions;

class AttachQueueListenersStrategyTest extends TestCase
{
    use ListenerAttachmentAssertions;

    protected $queue;
    protected $worker;
    /** @var AttachQueueListenersStrategy */
    protected $listener;
    protected $strategyManager;

    public function setUp(): void
    {
        $this->queue = $this->createStub(QueueInterface::class);
        $this->worker = new SimpleWorker();
        $this->createListener($this->createStub(StrategyPluginManager::class));
    }

    private function createListener(StrategyPluginManager $strategyManager): void
    {
        $this->strategyManager = $strategyManager;
        $this->listener = new AttachQueueListenersStrategy($this->strategyManager, [
            'queueName' => [
                'SlmQueue\Strategy\SomeStrategy',
            ],
        ]);
    }

    /**
     * Replaces the queue and strategy manager stubs with mocks, for tests that set expectations on them
     */
    private function useMocks(): void
    {
        $this->queue = $this->createMock(QueueInterface::class);
        $this->createListener($this->createMock(StrategyPluginManager::class));
    }

    public function testListenerInstanceOfAbstractStrategy(): void
    {
        static::assertInstanceOf(AbstractStrategy::class, $this->listener);
    }

    public function testListensToCorrectEventAtCorrectPriority(): void
    {
        $priority = 1;

        $this->assertListenerAttachments($this->listener, $priority, [
            [WorkerEventInterface::EVENT_BOOTSTRAP, [$this->listener, 'attachQueueListeners'], PHP_INT_MAX],
        ]);
    }

    public function testAttachQueueListenersDetachedSelfFromEventManager(): void
    {
        $this->useMocks();

        $eventManager = $this->createMock('Laminas\EventManager\EventManager');
        $this->worker = new SimpleWorker($eventManager);

        $eventManager->expects($this->once())->method('attach')
            ->with(WorkerEventInterface::EVENT_BOOTSTRAP, [$this->listener, 'attachQueueListeners'])
            ->willReturn([$this->listener, 'attachQueueListeners']);

        $eventManager->expects($this->once())->method('detach')
            ->with([$this->listener, 'attachQueueListeners'])
            ->willReturn(true);

        $this->queue->expects($this->once())->method('getName')->willReturn('queueName');
        $strategyMock = $this->createStub(SimpleStrategy::class);
        $this->strategyManager->expects($this->once())->method('get')->willReturn($strategyMock);

        // will attach WorkerEventInterface::EVENT_BOOTSTRAP callback to listener
        $this->listener->attach($eventManager);

        // should detach WorkerEventInterface::EVENT_BOOTSTRAP
        $this->listener->attachQueueListeners(new BootstrapEvent($this->worker, $this->queue));
    }

    public function testAttachQueueListenerFallbackToDefaultIfQueueNameIsNotMatched(): void
    {
        $this->useMocks();

        // let's change the config setup
        $class = new ReflectionClass(AttachQueueListenersStrategy::class);
        $strategyConfig = $class->getProperty('strategyConfig');
        $strategyConfig->setValue($this->listener, [
            'default' => [
                SimpleStrategy::class,
            ],
        ]);

        $this->queue->expects($this->once())->method('getName')->willreturn('nonConfiguredQueueName');
        $strategyMock = $this->createMock(SimpleStrategy::class);
        $strategyMock->expects($this->exactly(1))->method('attach');
        $this->strategyManager->expects($this->once())->method('get')->with(
            SimpleStrategy::class,
            []
        )->willReturn($strategyMock);

        $this->listener->attachQueueListeners(new BootstrapEvent($this->worker, $this->queue));
    }

    public function testAttachQueueListenersStrategyConfig(): void
    {
        $this->useMocks();

        // let's change the config setup
        $class = new ReflectionClass(AttachQueueListenersStrategy::class);
        $strategyConfig = $class->getProperty('strategyConfig');
        $strategyConfig->setValue($this->listener, [
            'queueName' => [
                'SlmQueue\Strategy\SomeStrategy',
                'SlmQueue\Strategy\OtherStrategy' => ['priority' => 3],
                'SlmQueue\Strategy\FinalStrategy' => ['foo' => 'bar'],
                'SlmQueue\Strategy\SomeStrategy' => 'not_an_array',
            ],
        ]);

        $eventManager = $this->worker->getEventManager();
        $this->queue->expects($this->once())->method('getName')->willreturn('queueName');

        $someStrategy = $this->createMock(ListenerAggregateInterface::class);
        $someStrategy->expects($this->exactly(1))->method('attach')->with($eventManager);

        $otherStrategy = $this->createMock(ListenerAggregateInterface::class);
        $otherStrategy->expects($this->exactly(1))->method('attach')->with($eventManager, 3);

        $finalStrategy = $this->createMock(ListenerAggregateInterface::class);
        $finalStrategy->expects($this->exactly(1))->method('attach')->with($eventManager);

        $strategies = [
            'SlmQueue\Strategy\SomeStrategy' => $someStrategy,
            'SlmQueue\Strategy\OtherStrategy' => $otherStrategy,
            'SlmQueue\Strategy\FinalStrategy' => $finalStrategy,
        ];
        $requested = [];
        $this->strategyManager->expects($this->exactly(3))->method('get')->willReturnCallback(
            function (string $name, ?array $options = null) use ($strategies, &$requested) {
                $requested[] = [$name, $options];

                return $strategies[$name];
            }
        );

        $this->listener->attachQueueListeners(new BootstrapEvent($this->worker, $this->queue));

        static::assertSame([
            ['SlmQueue\Strategy\SomeStrategy', []],
            ['SlmQueue\Strategy\OtherStrategy', []],
            ['SlmQueue\Strategy\FinalStrategy', ['foo' => 'bar']],
        ], $requested);
    }

    public function testAttachQueueListenersBootstrapEventIsTriggeredOnlyOnce(): void
    {
        $this->useMocks();

        $eventManager = $this->createMock(EventManager::class);
        $this->worker = new SimpleWorker($eventManager);
        $this->queue->expects($this->once())->method('getName')->willreturn('queueName');

        $strategyMock = $this->createMock(ListenerAggregateInterface::class);
        $strategyMock->expects($this->exactly(1))->method('attach')->with($eventManager);
        $this->strategyManager->expects($this->once())->method('get')->with(
            'SlmQueue\Strategy\SomeStrategy',
            []
        )->willReturn($strategyMock);

        $eventManager->expects($this->once())->method('triggerEvent');
        $this->listener->attachQueueListeners(new BootstrapEvent($this->worker, $this->queue));
    }
}
