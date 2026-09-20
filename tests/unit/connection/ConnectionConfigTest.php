<?php

declare(strict_types=1);


use Lb\RabbitMq\connection\ConnectionConfig;
use PHPUnit\Framework\TestCase;

class ConnectionConfigTest extends TestCase
{
    public function testCanCreateConnectionConfigSuccessfully(): void
    {
        $config = new ConnectionConfig(
            host: "localhost",
            port: 5672,
            user: "guest",
            password: "guest",
            vhost: "/",
            heartbeat: 30,
            connectionTimeout: 3.0,
            readWriteTimeout: 60.0
        );

        $this->assertInstanceOf(ConnectionConfig::class, $config);
    }

    public function testCantCreateConnectionConfigSuccessfully(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConnectionConfig(
            host: "localhost",
            port: 5672,
            user: "guest",
            password: "guest",
            vhost: "/",
            heartbeat: 30,
            connectionTimeout: 3.0,
            readWriteTimeout: 40.0
        );
    }

    public function testCanCreateConnectionConfigFromDSN(): void
    {
        $dsn = "amqp://guest:guest@localhost:5672/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0";
        $config = ConnectionConfig::fromDsn($dsn);

        $this->assertInstanceOf(ConnectionConfig::class, $config);
    }

    public function testCanNotCreateConnectionFromDSN(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $dsn = "invalid_dsn";

        ConnectionConfig::fromDsn($dsn);
    }

    public function testCanCreateConnectionFromEnv(): void
    {
        putenv("RABBITMQ_DSN=amqp://guest:guest@localhost:5672/%2F?heartbeat=30&connection_timeout=3.0&read_write_timeout=60.0");

        $config = ConnectionConfig::fromEnv();

        $this->assertInstanceOf(ConnectionConfig::class, $config);
    }

    public function testCanNotCreateConnectionFromEnv(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = ConnectionConfig::fromEnv("NON_EXISTENT_ENV_VAR");
    }
}