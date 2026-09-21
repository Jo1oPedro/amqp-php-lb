<?php

namespace Lb\RabbitMq\Topology;

enum ExchangeType: string
{
    /**
     * Routes messages to queues based on an exact match between the message's routing key and the queue's binding key.
     * Use case: Point-to-point messaging or task distribution where a specific worker queue handles specific tasks (e.g., an error log routing key goes to the error-handling queue).
     */
    case Direct = "direct";

    /**
     * How it works: Routes messages using wildcard pattern matching between the routing key and the binding key. It uses an asterisk (*) to substitute for exactly one word and a hash (#) to substitute for zero or more words.
     * Use case: Multicast messaging where consumers subscribe to categories or streams of data (e.g., a routing key like usa.news.tech matches bindings like *.news.#).
     */
    case Topic = "topic";

    /**
     * How it works: Broadcasts all received messages to every queue bound to it, completely ignoring routing keys.
     * Use case: Publish-subscribe scenarios where the exact same message needs to go to multiple different services or consumers simultaneously (e.g., updating caches across multiple servers).
     */
    case Fanout = "fanout";

    /**
     * How it works: Ignores routing keys entirely and routes messages based on matching key-value pairs inside the message header attributes (using an x-match argument set to all or any).
     * Use case: Complex routing scenarios driven by multiple metadata attributes rather than a single string routing key.
     */
    case Headers = "headers";
}