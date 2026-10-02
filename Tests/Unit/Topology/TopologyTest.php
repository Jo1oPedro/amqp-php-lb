<?php

namespace Lb\RabbitMq\Tests\Unit\Topology;

use Lb\RabbitMq\Topology\Binding;
use Lb\RabbitMq\Topology\Exchange;
use Lb\RabbitMq\Topology\Queue;
use Lb\RabbitMq\Topology\Topology;
use PHPUnit\Framework\TestCase;

class TopologyTest extends TestCase
{
    public function testCreateStartsEmpty(): void
    {
        $topology = Topology::create();

        $this->assertSame([], $topology->exchanges());
        $this->assertSame([], $topology->queues());
        $this->assertSame([], $topology->bindings());
    }

    public function testExchangesAreDeduplicatedByNameKeepingTheLast(): void
    {
        $first = Exchange::direct("orders");
        $last = Exchange::topic("orders");

        $topology = Topology::create()->exchange($first)->exchange($last);

        $this->assertCount(1, $topology->exchanges());
        $this->assertSame($last, $topology->exchanges()[0]);
    }

    public function testQueuesAreDeduplicatedByNameKeepingTheLast(): void
    {
        $first = Queue::quorum("orders");
        $last = Queue::classic("orders");

        $topology = Topology::create()->queue($first)->queue($last);

        $this->assertCount(1, $topology->queues());
        $this->assertSame($last, $topology->queues()[0]);
    }

    public function testBindingsAreNotDeduplicated(): void
    {
        $topology = Topology::create()
            ->bind("orders-exchange", "orders-queue", "created")
            ->bind("orders-exchange", "orders-queue", "created");

        $this->assertCount(2, $topology->bindings());
    }

    public function testBindCreatesQueueBinding(): void
    {
        $topology = Topology::create()->bind("orders-exchange", "orders-queue", "created");

        $binding = $topology->bindings()[0];

        $this->assertSame("orders-exchange", $binding->source);
        $this->assertSame("orders-queue", $binding->destination);
        $this->assertSame("created", $binding->routingKey);
        $this->assertFalse($binding->destinationIsExchange);
    }

    public function testBindDefaultsToEmptyRoutingKey(): void
    {
        $topology = Topology::create()->bind("logs-exchange", "logs-queue");

        $this->assertSame("", $topology->bindings()[0]->routingKey);
    }

    /**
     * Topology is mutable on purpose: unlike Queue/Exchange, the fluent methods
     * mutate and return the same instance instead of a clone.
     */
    public function testFluentMethodsMutateAndReturnTheSameInstance(): void
    {
        $topology = Topology::create();

        $this->assertSame($topology, $topology->exchange(Exchange::direct("orders")));
        $this->assertSame($topology, $topology->queue(Queue::quorum("orders")));
        $this->assertSame($topology, $topology->binding(Binding::queue("orders", "orders")));
        $this->assertSame($topology, $topology->bind("orders", "orders"));
    }

    public function testAccessorsReturnSequentialLists(): void
    {
        $topology = Topology::create()
            ->exchange(Exchange::direct("a"))
            ->exchange(Exchange::direct("b"))
            ->queue(Queue::quorum("x"))
            ->queue(Queue::quorum("y"));

        $this->assertSame([0, 1], array_keys($topology->exchanges()));
        $this->assertSame([0, 1], array_keys($topology->queues()));
    }

    public function testMergeCopiesExchangesQueuesAndBindings(): void
    {
        $other = Topology::create()
            ->exchange(Exchange::direct("orders-exchange"))
            ->queue(Queue::quorum("orders-queue"))
            ->bind("orders-exchange", "orders-queue", "created");

        $topology = Topology::create()->merge($other);

        $this->assertCount(1, $topology->exchanges());
        $this->assertCount(1, $topology->queues());
        $this->assertCount(1, $topology->bindings());
    }

    public function testMergeOverridesEntriesWithTheSameName(): void
    {
        $incoming = Exchange::topic("orders-exchange");

        $topology = Topology::create()
            ->exchange(Exchange::direct("orders-exchange"))
            ->merge(Topology::create()->exchange($incoming));

        $this->assertCount(1, $topology->exchanges());
        $this->assertSame($incoming, $topology->exchanges()[0]);
    }

    public function testMergeAppendsBindingsInsteadOfOverriding(): void
    {
        $topology = Topology::create()
            ->bind("orders-exchange", "orders-queue", "created")
            ->merge(Topology::create()->bind("orders-exchange", "orders-queue", "created"));

        $this->assertCount(2, $topology->bindings());
    }

    public function testMergeDoesNotMutateTheOtherTopology(): void
    {
        $other = Topology::create()->exchange(Exchange::direct("from-other"));

        Topology::create()
            ->exchange(Exchange::direct("from-self"))
            ->merge($other);

        $this->assertCount(1, $other->exchanges());
        $this->assertSame("from-other", $other->exchanges()[0]->name);
    }

    public function testMergeReturnsTheSameInstance(): void
    {
        $topology = Topology::create();

        $this->assertSame($topology, $topology->merge(Topology::create()));
    }
}
