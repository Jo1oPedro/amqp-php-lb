<?php

namespace Lb\RabbitMq\Tests\Unit\Topology;

use Lb\RabbitMq\Topology\Queue;
use Lb\RabbitMq\Topology\QueueType;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testQuorumQueueHasSafeDefaults(): void
    {
        $queue = Queue::quorum("orders");

        $this->assertSame("orders", $queue->name);
        $this->assertSame(QueueType::Quorum, $queue->type);
        $this->assertTrue($queue->durable);
        $this->assertFalse($queue->exclusive);
        $this->assertFalse($queue->autoDelete);
    }

    public function testExclusiveQueueIsTransientAndAutoDelete(): void
    {
        $queue = Queue::classic("temp")->exclusive("temp2");

        $this->assertSame("temp2", $queue->name);
        $this->assertSame(QueueType::Classic, $queue->type);
        $this->assertFalse($queue->durable);
        $this->assertTrue($queue->exclusive);
        $this->assertTrue($queue->autoDelete);
    }

    public function testWithArgumentsReturnsNewInstance(): void
    {
        $original = Queue::quorum("orders");
        $modified = $original->withArgument("x-max-length", 1000);

        $this->assertNotSame($original, $modified);
        $this->assertSame([], $original->arguments);
        $this->assertSame(["x-max-length" => 1000], $modified->arguments);
    }

    public function testMaxLengthAlsoSetsOverflowPolicy(): void
    {
        $queue = Queue::quorum("orders")->maxLength(100);

        $this->assertSame(
            ["x-max-length" => 100, "x-overflow" => "reject-publish"],
            $queue->arguments,
        );
    }

    public function testDeadLetterWithoutRoutingKeyOmitsIt(): void
    {
        $queue = Queue::quorum("orders")->deadLetterTo("dlx");

        $this->assertSame(["x-dead-letter-exchange" => "dlx"], $queue->arguments);
        $this->assertArrayNotHasKey("x-dead-letter-routing-key", $queue->arguments);
    }

    public function testDeliveryLimitIsRejectedForClassicQueues(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Queue::classic("orders")->deliveryLimit(5);
    }

    public function testAmqpArgumentsIncludesQueueType(): void
    {
        $queue = Queue::quorum("orders")->messageTtl(5000);

        $this->assertSame(
            ["x-message-ttl" => 5000, "x-queue-type" => "quorum"],
            $queue->amqpArguments(),
        );
    }

    public function testExpiresSetQueueExpiryArgument(): void
    {
        $queue = Queue::quorum("orders")->expires(60000);

        $this->assertSame(["x-expires" => 60000], $queue->arguments);
    }

    public function testSingleActiveConsumerSetArgumentsToTrue(): void
    {
        $queue = Queue::classic("orders")->singleActiveConsumer();

        $this->assertSame(["x-single-active-consumer" => true], $queue->arguments);
    }
}