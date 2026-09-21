<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Connection;
final readonly class ConnectionConfig
{
    public function __construct(
        public string $host = "localhost",
        public int $port = 5672,
        public string $user = "guest",
        public string $password = "guest",
        public string $vhost = "/",
        /** Seconds. 0 turnoff. Broker close conection after 2 intervals without sinal. */
        public int $heartbeat = 30,
        public float $connectionTimeout = 3.0,
        /** At least 2x the heartbeat time, to avoid socket expire before heartbeat comes */
        public float $readWriteTimeout = 60.0,
        public bool $keepAlive = true
    ) {
        if ($this->heartbeat > 0 && $this->readWriteTimeout < $this->heartbeat * 2) {
            throw new \InvalidArgumentException(
                "readWriteTimeout must be at least 2x heartbeat"
            );
        }
    }

    public static function fromDsn(string $dsn): self
    {
        $parts = parse_url($dsn);

        if($parts === false or ($parts["scheme"] ?? null) !== "amqp") {
            throw new \InvalidArgumentException("Invalid DSN: $dsn");
        }

        parse_str($parts["query"] ?? "", $query);

        $path = ltrim($parts["path"] ?? "", '/');
        $vhost = $path === "" ? "/" : rawurldecode($path);

        return new self(
            host: $parts["host"] ?? "localhost",
            port: (int) ($parts["port"] ?? 5672),
            user: rawurldecode($parts["user"] ?? "guest"),
            password: rawurldecode($parts["pass"] ?? "guest"),
            vhost: $vhost,
            heartbeat: (int) ($query["heartbeat"] ?? 30),
            connectionTimeout: (float) ($query["connection_timeout"] ?? 3.0),
            readWriteTimeout: (float) ($query["read_write_timeout"] ?? 60.0)
        );
    }

    public static function fromEnv(string $variable = "RABBITMQ_DSN"): self
    {
        $dsn = $_ENV[$variable] ?? getenv($variable);

        if ($dsn === false or $dsn === "") {
            throw new \InvalidArgumentException("Environment variable $variable not set");
        }

        return self::fromDsn($dsn);
    }
}