<?php

use Dotenv\Dotenv;
use Lb\RabbitMq\Connection\ConnectionConfig;
use Lb\RabbitMq\Connection\ConnectionFactory;
use Lb\RabbitMq\Topology\Exchange;
use Lb\RabbitMq\Topology\Queue;
use Lb\RabbitMq\Topology\Topology;
use Lb\RabbitMq\Topology\TopologyDeclarer;

require_once __DIR__ . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$topology = Topology::create()
    ->exchange(Exchange::topic("orders")->alternateExchange("orders.unrouted"))
    ->exchange(Exchange::topic("orders.dlx"))
    ->exchange(Exchange::fanout("orders.unrouted"))
    ->queue(
        Queue::quorum("orders.payment")
            ->deadLetterTo("orders.dlx", "orders.payment.failed")
            ->deliveryLimit(5)
    )
    ->queue(Queue::quorum("orders.payment.parking"))
    ->queue(Queue::quorum("orders.unrouted"))
    ->bind("orders", "orders.payment", "order.created")
    ->bind("orders", "orders.payment", "order.cancelled")
    ->bind("orders.dlx", "orders.payment.parking", "orders.payment.failed")
    ->bind("orders.unrouted", "orders.unrouted");

$factory = new ConnectionFactory(ConnectionConfig::fromEnv());

$topologyDeclarer = new TopologyDeclarer($factory);

$topologyDeclarer->declare($topology);