<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Tests\Integration\Topology;

use Lb\RabbitMq\Connection\ConnectionConfig;
use Lb\RabbitMq\Connection\ConnectionFactory;
use Lb\RabbitMq\Exception\TopologyException;
use Lb\RabbitMq\Topology\Binding;
use Lb\RabbitMq\Topology\Exchange;
use Lb\RabbitMq\Topology\Queue;
use Lb\RabbitMq\Topology\Topology;
use Lb\RabbitMq\Topology\TopologyDeclarer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class TopologyDeclarerTest extends TestCase
{
    private ConnectionFactory $factory;

    private TopologyDeclarer $declarer;

    /** Unique per test, so a leftover from a previous run never masks a failure. */
    private string $prefix;

    /** @var list<string> */
    private array $declaredQueues = [];

    /** @var list<string> */
    private array $declaredExchanges = [];

    protected function setUp(): void
    {
        $this->factory = new ConnectionFactory(
            ConnectionConfig::fromDsn($_ENV["RABBITMQ_DSN"])
        );
        $this->declarer = new TopologyDeclarer($this->factory);
        $this->prefix = "test-" . bin2hex(random_bytes(4)) . "-";
    }

    protected function tearDown(): void
    {
        foreach ($this->declaredQueues as $queue) {
            $this->silentlyDelete(fn(AMQPChannel $channel) => $channel->queue_delete($queue));
        }

        foreach ($this->declaredExchanges as $exchange) {
            $this->silentlyDelete(fn(AMQPChannel $channel) => $channel->exchange_delete($exchange));
        }

        $this->factory->close();
    }

    public function testDeclaresExchangesAndQueues(): void
    {
        $exchange = $this->exchangeName("orders");
        $queue = $this->queueName("orders");

        $this->declarer->declare(
            Topology::create()
                ->exchange(Exchange::topic($exchange))
                ->queue(Queue::quorum($queue))
                ->bind($exchange, $queue, "order.created")
        );

        $this->assertTrue($this->exchangeExists($exchange));
        $this->assertTrue($this->queueExists($queue));
    }

    public function testDeclaredBindingRoutesMessagesToTheQueue(): void
    {
        $exchange = $this->exchangeName("orders");
        $queue = $this->queueName("orders");

        $this->declarer->declare(
            Topology::create()
                ->exchange(Exchange::topic($exchange))
                ->queue(Queue::quorum($queue))
                ->bind($exchange, $queue, "order.*")
        );

        $this->publish($exchange, "order.created", "payload");

        $this->assertSame("payload", $this->popMessageBody($queue));
    }

    public function testDeclaredExchangeToExchangeBindingRoutesMessages(): void
    {
        $source = $this->exchangeName("upstream");
        $destination = $this->exchangeName("downstream");
        $queue = $this->queueName("downstream");

        $this->declarer->declare(
            Topology::create()
                ->exchange(Exchange::fanout($source))
                ->exchange(Exchange::fanout($destination))
                ->queue(Queue::quorum($queue))
                ->binding(Binding::exchange($source, $destination))
                ->bind($destination, $queue)
        );

        $this->publish($source, "", "forwarded");

        $this->assertSame("forwarded", $this->popMessageBody($queue));
    }

    public function testDeclareIsIdempotent(): void
    {
        $topology = Topology::create()
            ->exchange(Exchange::direct($this->exchangeName("orders")))
            ->queue(Queue::quorum($this->queueName("orders")));

        $this->declarer->declare($topology);
        $this->declarer->declare($topology);

        $this->assertTrue($this->queueExists($this->queueName("orders")));
    }

    /** The declarer must close its channel on every call, or it exhausts the connection's channel limit. */
    public function testDeclareDoesNotLeakChannels(): void
    {
        $topology = Topology::create()->queue(Queue::quorum($this->queueName("orders")));

        for ($i = 0; $i < 60; $i++) {
            $this->declarer->declare($topology);
        }

        $this->assertTrue($this->queueExists($this->queueName("orders")));
    }

    public function testRedeclaringQueueWithDifferentTypeThrowsTopologyException(): void
    {
        $queue = $this->queueName("orders");

        $this->declarer->declare(Topology::create()->queue(Queue::quorum($queue)));

        try {
            $this->declarer->declare(Topology::create()->queue(Queue::classic($queue)));
            $this->fail("Expected a TopologyException for an inequivalent redeclaration.");
        } catch (TopologyException $exception) {
            $this->assertStringContainsString("Resource already exists", $exception->getMessage());
            $this->assertInstanceOf(
                AMQPProtocolChannelException::class,
                $exception->getPrevious()
            );
        }
    }

    public function testInvalidArgumentForQueueTypeThrowsTopologyException(): void
    {
        $queue = $this->queueName("orders");

        // x-delivery-limit only exists for quorum queues; forced onto a classic one
        // the broker answers 406 with "invalid arg".
        $topology = Topology::create()->queue(
            Queue::classic($queue)->withArgument("x-delivery-limit", 3)
        );

        try {
            $this->declarer->declare($topology);
            $this->fail("Expected a TopologyException for an invalid argument.");
        } catch (TopologyException $exception) {
            $this->assertStringContainsString("Invalid argument", $exception->getMessage());
        }
    }

    public function testFailedDeclarationKeepsTheConnectionUsable(): void
    {
        $queue = $this->queueName("orders");
        $this->declarer->declare(Topology::create()->queue(Queue::quorum($queue)));

        try {
            $this->declarer->declare(Topology::create()->queue(Queue::classic($queue)));
        } catch (TopologyException) {
            // expected
        }

        $other = $this->queueName("other");
        $this->declarer->declare(Topology::create()->queue(Queue::quorum($other)));

        $this->assertTrue($this->queueExists($other));
    }

    public function testDeleteRemovesQueuesAndExchanges(): void
    {
        $exchange = $this->exchangeName("orders");
        $queue = $this->queueName("orders");

        $topology = Topology::create()
            ->exchange(Exchange::direct($exchange))
            ->queue(Queue::quorum($queue))
            ->bind($exchange, $queue, "created");

        $this->declarer->declare($topology);
        $this->declarer->delete($topology);

        $this->assertFalse($this->queueExists($queue));
        $this->assertFalse($this->exchangeExists($exchange));
    }

    /**
     * RabbitMQ answers queue.delete/exchange.delete for a missing resource with
     * success, so deleting a topology that was never declared is a no-op.
     */
    public function testDeletingUnknownTopologyIsANoOp(): void
    {
        $this->declarer->delete(
            Topology::create()
                ->exchange(Exchange::direct($this->exchangeName("never-declared")))
                ->queue(Queue::quorum($this->queueName("never-declared")))
        );

        $this->assertFalse($this->queueExists($this->queueName("never-declared")));
    }

    private function exchangeName(string $suffix): string
    {
        $name = $this->prefix . $suffix;
        if (!in_array($name, $this->declaredExchanges, true)) {
            $this->declaredExchanges[] = $name;
        }

        return $name;
    }

    private function queueName(string $suffix): string
    {
        $name = $this->prefix . $suffix;
        if (!in_array($name, $this->declaredQueues, true)) {
            $this->declaredQueues[] = $name;
        }

        return $name;
    }

    private function queueExists(string $name): bool
    {
        return $this->existsPassively(fn($channel) => $channel->queue_declare($name, passive: true));
    }

    private function exchangeExists(string $name): bool
    {
        return $this->existsPassively(
            fn($channel) => $channel->exchange_declare($name, "direct", passive: true)
        );
    }

    /** A failed passive declare closes the channel, so each check gets a fresh one. */
    private function existsPassively(callable $probe): bool
    {
        $channel = $this->factory->channel();

        try {
            $probe($channel);

            return true;
        } catch (AMQPProtocolChannelException) {
            return false;
        } finally {
            if ($channel->is_open()) {
                $channel->close();
            }
        }
    }

    private function publish(string $exchange, string $routingKey, string $body): void
    {
        $channel = $this->factory->channel();

        try {
            $channel->basic_publish(new AMQPMessage($body), $exchange, $routingKey);
        } finally {
            $channel->close();
        }
    }

    private function popMessageBody(string $queue): ?string
    {
        $channel = $this->factory->channel();

        try {
            $message = $channel->basic_get($queue);

            if ($message === null) {
                return null;
            }

            $channel->basic_ack($message->getDeliveryTag());

            return $message->getBody();
        } finally {
            $channel->close();
        }
    }

    private function silentlyDelete(callable $delete): void
    {
        $channel = $this->factory->channel();

        try {
            $delete($channel);
        } catch (\Throwable) {
            // already gone
        } finally {
            if ($channel->is_open()) {
                $channel->close();
            }
        }
    }
}
