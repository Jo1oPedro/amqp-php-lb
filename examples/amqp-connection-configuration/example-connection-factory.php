<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

use Lb\RabbitMq\Connection\ConnectionConfig;
use Lb\RabbitMq\Connection\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

$amqpConnection = new ConnectionFactory(
    ConnectionConfig::fromEnv()
)->connection();

$channel = $amqpConnection->channel();

$queue = "smoke.test";

$channel->queue_declare($queue, false, true, false, false);

$channel->basic_publish(
    new AMQPMessage(
        "hello from " . gethostname(),
        ["delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    ),
    routing_key: $queue
);

$recieved = $channel->basic_get($queue, no_ack: true);

echo $recieved === null ? "Nothing recieved\n" : "Recieved: {$recieved->getBody()}\n";

$channel->queue_delete($queue);
$channel->close();
$amqpConnection->close();