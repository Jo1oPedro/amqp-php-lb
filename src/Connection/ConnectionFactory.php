<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Connection;

use Lb\RabbitMq\Exception\ConnectionException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;

final class ConnectionFactory
{
    private ?AbstractConnection $amqpConnection = null;

    public function __construct(
        private readonly ConnectionConfig $connectionConfig
    ) {}

    public function connection(): AbstractConnection
    {
        if($this->amqpConnection === null || !$this->amqpConnection->isConnected()) {
            $this->amqpConnection = $this->open();
        }

        return $this->amqpConnection;
    }

    public function channel(): AMQPChannel
    {
        return $this->connection()->channel();
    }

    public function close(): void
    {
        if($this->amqpConnection === null) {
            return;
        }

        try {
            $this->amqpConnection->close();
        } catch (\Throwable) {

        } finally {
           $this->amqpConnection = null;
        }
    }

    private function open(): AbstractConnection
    {
        $amqpConnection = new AMQPConnectionConfig();
        $amqpConnection->setHost($this->connectionConfig->host);
        $amqpConnection->setPort($this->connectionConfig->port);
        $amqpConnection->setUser($this->connectionConfig->user);
        $amqpConnection->setPassword($this->connectionConfig->password);
        $amqpConnection->setVhost($this->connectionConfig->vhost);
        $amqpConnection->setHeartbeat($this->connectionConfig->heartbeat);
        $amqpConnection->setKeepalive($this->connectionConfig->keepAlive);
        $amqpConnection->setConnectionTimeout($this->connectionConfig->connectionTimeout);
        $amqpConnection->setReadTimeout($this->connectionConfig->readWriteTimeout);
        $amqpConnection->setWriteTimeout($this->connectionConfig->readWriteTimeout);

        try {
            return AMQPConnectionFactory::create($amqpConnection);
        } catch (\Throwable $e) {
            throw new ConnectionException(
                sprintf(
                    "Could not connect to %s:%d (vhost %s): %s",
                    $this->connectionConfig->host,
                    $this->connectionConfig->port,
                    $this->connectionConfig->vhost,
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }
}