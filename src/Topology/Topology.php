<?php

namespace Lb\RabbitMq\Topology;

final class Topology
{
    private array $exchanges = [];

    private array $queues = [];

    private array $bindings = [];

    public static function create(): self
    {
        return new self();
    }

    public function exchange(Exchange $exchange): self
    {
        $this->exchanges[$exchange->name] = $exchange;
        return $this;
    }

    public function queue(Queue $queue): self
    {
        $this->queues[$queue->name] = $queue;
        return $this;
    }

    public function binding(Binding $binding): self
    {
        $this->bindings[] = $binding;
        return $this;
    }

    public function bind(string $exchange, string $queue, string $routingKey = ""): self
    {
        return $this->binding(Binding::queue($exchange, $queue, $routingKey));
    }

    public function merge(Topology $other): self
    {
        foreach($other->exchanges as $exchange) {
            $this->exchange($exchange);
        }

        foreach($other->queues as $queue) {
            $this->queue($queue);
        }

        foreach($other->bindings as $binding) {
            $this->binding($binding);
        }

        return $this;
    }

    /** @return list<Exchange> */
    public function exchanges(): array
    {
        return array_values($this->exchanges);
    }

    /** @return list<Queue> */
    public function queues(): array
    {
        return array_values($this->queues);
    }

    /** @return list<Binding> */
    public function bindings(): array
    {
        return $this->bindings;
    }
}