<?php

namespace Lb\RabbitMq\Topology;

/**
 * An exchange in RabbitMQ is a message-routing agent that receives messages from a producer and pushes them to one or more queues
 * Think of it like a post office or a mail sorting center: producers do not send messages directly to queues;
 * instead, they hand them to an exchange, which reads the message details and decides where the mail needs to go.
 */
class Exchange
{
    /** @param array<string, mixed> $arguments */
    private function __construct(
        public string $name,
        public ExchangeType $type,
        public bool $durable = true,
        public bool $autoDelete = false,
        public bool $internal = false,
        public array $arguments = []
    ) {}

    public static function direct(string $name): self
    {
        return new self($name, ExchangeType::Direct);
    }

    public static function topic(string $name): self
    {
        return new self($name, ExchangeType::Topic);
    }

    public static function fanout(string $name): self
    {
        return new self($name, ExchangeType::Fanout);
    }

    public static function headers(string $name): self
    {
        return new self($name, ExchangeType::Headers);
    }

    /** Does not survive a broker restart. Useful for test/temporary exchanges */
    public function transient(): self
    {
        return clone($this, ["durable" => false]);
    }

    /** Exchange will be removed when the last queue connected to it turn off */
    public function autoDelete(): self
    {
        return clone($this, ["autoDelete" => true]);
    }

    /** Only receives messages from others exchanges, never from publishers */
    public function internal(): self
    {
        return clone($this, ["internal" => true]);
    }

    /** Messages that do not match any binding go to this exchange, instead of being discarded */
    public function alternateExchange(string $exchange): self
    {
        return $this->withArgument("alternate-exchange", $exchange);
    }

    public function withArgument(string $key, mixed $value): self
    {
        return clone($this, ["arguments" => [...$this->arguments, $key => $value]]);
    }
}