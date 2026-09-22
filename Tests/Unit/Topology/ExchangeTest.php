<?php

declare(strict_types=1);

namespace Lb\RabbitMq\Tests\Unit\Topology;

use Lb\RabbitMq\Topology\Exchange;
use Lb\RabbitMq\Topology\ExchangeType;
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    public function testCanCreateDirectExchangeSuccessfully(): void
    {
        $exchange = Exchange::direct('my_exchange');

        $this->assertEquals('my_exchange', $exchange->name);
        $this->assertEquals(ExchangeType::Direct, $exchange->type);
        $this->assertTrue($exchange->durable);
        $this->assertFalse($exchange->autoDelete);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testCanCreateTopicExchangeSuccessfully(): void
    {
        $exchange = Exchange::topic('my_topic_exchange');

        $this->assertEquals('my_topic_exchange', $exchange->name);
        $this->assertEquals(ExchangeType::Topic, $exchange->type);
        $this->assertTrue($exchange->durable);
        $this->assertFalse($exchange->autoDelete);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testCanCreateFanoutExchangeSuccessfully(): void
    {
        $exchange = Exchange::fanout('my_fanout_exchange');

        $this->assertEquals('my_fanout_exchange', $exchange->name);
        $this->assertEquals(ExchangeType::Fanout, $exchange->type);
        $this->assertTrue($exchange->durable);
        $this->assertFalse($exchange->autoDelete);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testCanCreateHeaderExchangeSuccessfully(): void
    {
        $exchange = Exchange::headers('my_header_exchange');

        $this->assertEquals('my_header_exchange', $exchange->name);
        $this->assertEquals(ExchangeType::Headers, $exchange->type);
        $this->assertTrue($exchange->durable);
        $this->assertFalse($exchange->autoDelete);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testTransientExchangeIsNotDurable(): void
    {
        $exchange = Exchange::direct('my_exchange')->transient();

        $this->assertFalse($exchange->durable);
    }

    public function testTransientExchangeDoesNotChangeOtherFlags(): void
    {
        $exchange = Exchange::direct('my_exchange')->transient();

        $this->assertFalse($exchange->autoDelete);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testAutoDeleteExchangeIsAutoDeleted(): void
    {
        $exchange = Exchange::direct('my_exchange')->autoDelete();

        $this->assertTrue($exchange->autoDelete);
    }

    public function testAutoDeleteExchangeDoesNotChangeOtherFlags(): void
    {
        $exchange = Exchange::direct('my_exchange')->autoDelete();

        $this->assertTrue($exchange->durable);
        $this->assertFalse($exchange->internal);
        $this->assertEmpty($exchange->arguments);
    }

    public function testAlternateExchangeSetsAlternateExchangeArgument(): void
    {
        $exchange = Exchange::direct('my_exchange')->alternateExchange('my_alternate_exchange');

        $this->assertEquals('my_alternate_exchange', $exchange->arguments['alternate-exchange']);
    }

    public function testWithArgumentReturnsNewInstanceWithArgumentsAdded(): void
    {
        $original = Exchange::direct('my_exchange');
        $modified = $original->withArgument('x-argument', 'value');

        $this->assertNotSame($original, $modified);
        $this->assertSame([], $original->arguments);
        $this->assertSame(["x-argument" => "value"], $modified->arguments);
    }

    public function testWithArgumentAccumulatesPreviousArguments(): void
    {
        $exchange = Exchange::direct("my_exchange")
            ->withArgument("x-foo", "old")
            ->withArgument("x-foo", "new");

        $this->assertSame("new", $exchange->arguments["x-foo"]);
    }
}