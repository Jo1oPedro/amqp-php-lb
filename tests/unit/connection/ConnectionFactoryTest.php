<?php

use Lb\RabbitMq\connection\ConnectionConfig;
use Lb\RabbitMq\connection\ConnectionFactory as LbConnectionFactory;
use Lb\RabbitMq\Exception\ConnectionException;
use PhpAmqpLib\Connection\AbstractConnection;

use PHPUnit\Framework\TestCase;

class ConnectionFactoryTest extends TestCase
{
    public function testCanCreateConnectionSuccessfully(): void
    {
        $connectionConfig = ConnectionConfig::fromDsn(
            "amqp://guest:guest@rabbitmq:5672/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0"
        );
        $connectionFactory = new LbConnectionFactory($connectionConfig);

        $connection = $connectionFactory->connection();

        $this->assertInstanceOf(AbstractConnection::class, $connection);
    }

    public function testAllConnectionsFromSameConnectionFactoryAreTheSame(): void
    {
        $connectionConfig = ConnectionConfig::fromDsn(
            "amqp://guest:guest@rabbitmq:5672/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0"
        );
        $connectionFactory = new LbConnectionFactory($connectionConfig);

        $connection1 = $connectionFactory->connection();
        $connection2 = $connectionFactory->connection();

        $this->assertSame($connection1, $connection2);
    }

    public function testConnectionIsClosedSuccessfully(): void
    {
        $connectionConfig = ConnectionConfig::fromDsn(
            "amqp://guest:guest@rabbitmq:5672/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0"
        );
        $connectionFactory = new LbConnectionFactory($connectionConfig);

        $connection = $connectionFactory->connection();
        $this->assertTrue($connection->isConnected());

        $connectionFactory->close();
        $this->assertFalse($connection->isConnected());
    }

    public function testConnectionOpenThrowsException(): void
    {
        $this->expectException(ConnectionException::class);

        $connectionConfig = ConnectionConfig::fromDsn(
            "amqp://guest:guest@rabbitmq:1/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0"
        );
        new LbConnectionFactory($connectionConfig)->connection();

    }
}