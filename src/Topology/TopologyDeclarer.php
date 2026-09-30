<?php

namespace Lb\RabbitMq\Topology;

use Lb\RabbitMq\Connection\ConnectionFactory;
use Lb\RabbitMq\Exception\TopologyException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;

final class TopologyDeclarer
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory
    ) {}

    public function declare(Topology $topology): void
    {
        $channel = $this->connectionFactory->channel();

        try {
            foreach($topology->exchanges() as $exchange) {
                $channel->exchange_declare(
                    $exchange->name,
                    $exchange->type->value,
                    durable: $exchange->durable,
                    auto_delete: $exchange->autoDelete,
                    internal: $exchange->internal,
                    arguments: new AMQPTable($exchange->arguments)
                );
            }

            foreach($topology->queues() as $queue) {
                $channel->queue_declare(
                    $queue->name,
                    durable: $queue->durable,
                    exclusive: $queue->exclusive,
                    auto_delete: $queue->autoDelete,
                    arguments: new AMQPTable($queue->amqpArguments())
                );
            }

            foreach($topology->bindings() as $binding) {
                $arguments = new AMQPTable($binding->arguments);

                if($binding->destinationIsExchange) {
                    $channel->exchange_bind($binding->destination, $binding->source, $binding->routingKey, arguments: $arguments);
                } else {
                    $channel->queue_bind($binding->destination, $binding->source, $binding->routingKey, arguments: $arguments);
                }
            }
        } catch (AMQPProtocolChannelException $exception) {
            throw new TopologyException(
                $this->explain($exception),
                previous: $exception
            );
        } finally {
            if($channel->is_open()) {
                $channel->close();
            }
        }
    }

    public function delete(Topology $topology): void
    {
        $channel = $this->connectionFactory->channel();

        try {
            foreach($topology->queues() as $queue) {
                $channel->queue_delete($queue->name);
            }

            foreach($topology->exchanges() as $exchange) {
                $channel->exchange_delete($exchange->name);
            }
        } finally {
            if($channel->is_open()) {
                $channel->close();
            }
        }
    }

    private function explain(AMQPProtocolChannelException $e): string
    {
        $detail = $e->getMessage();

        if ($e->getCode() !== 406) {
            return 'Failed to declare topology: ' . $detail;
        }

        if (str_contains($detail, 'invalid arg')) {
            return 'Invalid argument for the queue/exchange type. Check if the type '
                . '(x-queue-type) is being sent and if the argument exists in this type. Detail: ' . $detail;
        }

        return 'Resource already exists with different parameter. RabbitMQ doesnt change existing exchange/queue '
            . ': delete it or use another name. Detail: ' . $detail;
    }
}