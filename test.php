<?php

require_once 'vendor/autoload.php';

use Lb\RabbitMq\Connection\ConnectionConfig;
use Lb\RabbitMq\Connection\ConnectionFactory;

$connectionConfig = ConnectionConfig::fromEnv();

$connection = new ConnectionFactory($connectionConfig)->connection();

dd($connection);