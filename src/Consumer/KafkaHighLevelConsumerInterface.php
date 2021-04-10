<?php

declare(strict_types=1);

namespace PhpKafka\Consumer;

use PhpKafka\Message\KafkaConsumerMessageInterface;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;

interface KafkaHighLevelConsumerInterface extends KafkaConsumerInterface
{
    /**
     * Assigns a consumer to the given TopicPartition(s)
     *
     * @param string[]|SkcTopicPartition[] $topicPartitions
     * @return void
     */
    public function assign(array $topicPartitions): void;

    /**
     * Asynchronous version of commit (non blocking)
     *
     * @param KafkaConsumerMessageInterface|KafkaConsumerMessageInterface[] $messages
     * @return void
     */
    public function commitAsync($messages): void;

    /**
     * Gets the current assignment for the consumer
     *
     * @return array|SkcTopicPartition[]
     */
    public function getAssignment(): array;

    /**
     * Gets the commited offset for a TopicPartition for the configured consumer group
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @param integer                       $timeoutMs
     * @return array|SkcTopicPartition[]
     */
    public function getCommittedOffsets(array $topicPartitions, int $timeoutMs): array;

    /**
     * Get current offset positions of the consumer
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @return array|SkcTopicPartition[]
     */
    public function getOffsetPositions(array $topicPartitions): array;

    /**
     * Close the consumer connection
     *
     * @return void;
     */
    public function close(): void;
}
