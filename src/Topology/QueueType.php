<?php

namespace Lb\RabbitMq\Topology;

enum QueueType: string
{
    /**
     * What it is: The original, traditional queue type in RabbitMQ.
     * How it works: It is a non-replicated, standard First-In, First-Out (FIFO) queue that lives on a single node
     * Best for: Applications requiring high throughput and low latency where losing messages due to a node crash is not catastrophic (e.g., real-time metrics, stateless processing).
     * Key limitation: It lacks native high availability
     * Destructive (Deleted after ACK)
     */
    case Classic = "classic";

    /**
     * What it is: The modern standard for data safety and high availability.
     * How it works: It replicates data across multiple cluster nodes using the Raft consensus algorithm. If the master node goes down, a new leader is elected automatically.
     * Best for: Mission-critical, data-sensitive applications where message loss is unacceptable (e.g., financial transactions, order processing).
     * Key limitation: It incurs higher resource overhead and slightly lower throughput compared to Classic queues due to replication overhead.
     * Destructive (Deleted after ACK)
     */
    case Quorum = "quorum";

    /**
     * What it is: A persistent, replicated, append-only log model (very similar to how Apache Kafka behaves).
     * How it works: Messages are not deleted after a consumer reads them. Instead, they remain stored, and consumers can repeatedly read or "time-travel" (replay) historical data.
     * High-volume event streaming, log aggregation, and large-scale data processing that requires high fan-out.
     * Non-destructive (Supports Replay)
     */
    case Stream = "stream";
}
