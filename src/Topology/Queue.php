<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Topology;
final readonly class Queue
{
    /** @param array<string, mixed> $arguments */
    private function __construct(
        public string $name,
        public QueueType $type,
        public bool $durable = true,
        public bool $exclusive = false,
        public bool $autoDelete = false,
        public array $arguments = []
    ) {
        if(
            $this->type === QueueType::Quorum &&
            (!$this->durable || $this->exclusive || $this->autoDelete)
        ) {
            throw new \InvalidArgumentException("Quorum queues must be durable, non-exclusive, and non-auto-delete.");
        }
    }

    public static function quorum(string $name): self
    {
        return new self($name, QueueType::Quorum);
    }

    //
    public static function classic(string $name): self
    {
        return new self($name, QueueType::Classic);
    }

    public function exclusive(string $name = ""): self
    {
        return new self($name, QueueType::Classic, durable: false, exclusive: true, autoDelete: true);
    }

    public function withArgument(string $key, mixed $value): self
    {
        return clone($this, ['arguments' => [...$this->arguments, $key => $value]]);
    }

    /**
     * Where rejected messages go (nack/reject without requeue) ou expired.
     * Without routingKey, keeps the original message routing key.
     */
    public function deadLetterTo(string $exchange, ?string $routingKey = null): self
    {
        $queue = $this->withArgument("x-dead-letter-exchange", $exchange);

        return $routingKey === null
            ? $queue
            : $queue->withArgument("x-dead-letter-routing-key", $routingKey);
    }

    /** Message expires after X ms in queue. */
    public function messageTtl(int $milliseconds): self
    {
        return $this->withArgument("x-message-ttl", $milliseconds);
    }

    /** Queue gets deleted after X ms without use(without consumers, get and etc) */
    public function expires(int $milliseconds): self
    {
        return $this->withArgument("x-expires", $milliseconds);
    }

    /** Message limit. Surplus is dead-lettered (with theres dlx) or discarded */
    public function maxLength(int $messages): self
    {
        return $this
            ->withArgument("x-max-length", $messages)
            ->withArgument("x-overflow", "reject-publish");
    }

    /**
     * Quorum: after X redeliveries messages goes to dlx
     * Native protection against poison messages in requeue loop
     */
    public function deliveryLimit(int $attempts): self
    {
        if($this->type !== QueueType::Quorum) {
            throw new \InvalidArgumentException("Delivery limit is only supported for quorum queues.");
        }

        return $this->withArgument("x-delivery-limit", $attempts);
    }

    /** Only one consumer active per time. All others are placed on hold. Ensures order with failover*/
    public function singleActiveConsumer(): self
    {
        return $this->withArgument("x-single-active-consumer", true);
    }

    public function amqpArguments(): array
    {
        return [...$this->arguments, "x-queue-type" => $this->type->value];
    }
}