<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Tests\Integration\Connection;

use Lb\RabbitMq\Connection\ConnectionConfig;
use Lb\RabbitMq\Connection\ConnectionFactory;
use Lb\RabbitMq\Exception\ConnectionException;
use PhpAmqpLib\Connection\AbstractConnection;
use PHPUnit\Framework\TestCase;

class ConnectionFactoryTest extends TestCase
{
    private ConnectionFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ConnectionFactory(
            ConnectionConfig::fromDsn($_ENV["RABBITMQ_DSN"])
        );
    }

    protected function tearDown(): void
    {
        $this->factory->close();
    }

    public function testCanCreateConnectionSuccessfully(): void
    {
        $connection = $this->factory->connection();
        $this->assertInstanceOf(AbstractConnection::class, $connection);
        $this->assertTrue($connection->isConnected());
    }

    public function testAllConnectionsFromSameConnectionFactoryAreTheSame(): void
    {
        $this->assertSame(
            $this->factory->connection(),
            $this->factory->connection()
        );
    }

    public function testConnectionIsClosedSuccessfully(): void
    {
        $connection = $this->factory->connection();;
        $this->assertTrue($connection->isConnected());

        $this->factory->close();
        $this->assertFalse($connection->isConnected());
    }

    public function testConnectionOpenThrowsException(): void
    {
        $this->expectException(ConnectionException::class);

        $connectionConfig = ConnectionConfig::fromDsn(
            "amqp://guest:guest@rabbitmq:1/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0"
        );

        new ConnectionFactory($connectionConfig)->connection();
    }
}