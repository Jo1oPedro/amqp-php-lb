<?php

require_once 'vendor/autoload.php';

use Lb\RabbitMq\connection\ConnectionConfig;
use Lb\RabbitMq\connection\ConnectionFactory;

$connectionConfig = ConnectionConfig::fromEnv();

$connection = new ConnectionFactory($connectionConfig)->connection();

dd($connection);