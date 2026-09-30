<?php

namespace Lb\RabbitMq\Topology;

final readonly class Binding
{
    private function __construct(
        public string $source,
        public string $destination,
        public string $routingKey = "",
        public bool $destinationIsExchange = false,
        public array $arguments = []
    ) {}

    public static function queue(string $exchange, string $queue, string $routingKey = ""): self
    {
        return new self(
            source: $exchange,
            destination: $queue,
            routingKey: $routingKey,
            destinationIsExchange: false
        );
    }

    public static function exchange(string $source, string $destination, string $routingKey = ""): self
    {
        return new self(
            source: $source,
            destination: $destination,
            routingKey: $routingKey,
            destinationIsExchange: true
        );
    }

    public function withHeaders(array $headers, bool $matchAll = true): self
    {
        return clone($this, [
            "arguments" => [...$headers, "x-match" => $matchAll ? "all" : "any"]
        ]);
    }
}