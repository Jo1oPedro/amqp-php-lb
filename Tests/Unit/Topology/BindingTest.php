<?php

namespace Lb\RabbitMq\Tests\Unit\Topology;

use Lb\RabbitMq\Topology\Binding;
use PHPUnit\Framework\TestCase;

class BindingTest extends TestCase
{
    public function testQueueBindingTargetsQueueDestination(): void
    {
        $binding = Binding::queue("teste", "teste2", "teste");
        self::assertFalse($binding->destinationIsExchange);
    }

    public function testExchangeBindingTargetsExchangeDestination(): void
    {
        $binding = Binding::exchange("teste", "teste2", "teste");
        self::assertTrue($binding->destinationIsExchange);
    }
}