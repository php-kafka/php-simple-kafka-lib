<?php

declare(strict_types=1);

namespace PhpKafka\Consumer;

use PhpKafka\Message\KafkaConsumerMessageInterface;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;

interface KafkaConsumerInterface extends KafkaConsumerInterface
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

    /**
     * Subscribes to all defined topics, if no partitions were set, subscribes to all partitions.
     * If partition(s) (and optionally offset(s)) were set, subscribes accordingly
     *
     * @return void
     */
    public function subscribe(): void;

    /**
     * Unsubscribes from the current subscription / assignment
     *
     * @return void
     */
    public function unsubscribe(): void;

    /**
     * Returns true if the consumer has subscribed to its topics, otherwise false
     * It is mandatory to call `subscribe` before `consume`
     *
     * @return boolean
     */
    public function isSubscribed(): bool;

    /**
     * Consumes a message and returns it
     * In cases of errors / timeouts a KafkaConsumerConsumeException is thrown
     *
     * @param integer $timeoutMs
     * @param boolean $autoDecode
     * @return KafkaConsumerMessageInterface
     */
    public function consume(int $timeoutMs = 10000, bool $autoDecode = true): KafkaConsumerMessageInterface;

    /**
     * Decode consumer message
     *
     * @param KafkaConsumerMessageInterface $message
     * @return KafkaConsumerMessageInterface
     */
    public function decodeMessage(KafkaConsumerMessageInterface $message): KafkaConsumerMessageInterface;

    /**
     * Commits the offset to the broker for the given message(s)
     *
     * @param KafkaConsumerMessageInterface|KafkaConsumerMessageInterface[] $messages
     * @return void
     */
    public function commit($messages): void;

    /**
     * Returns the configuration settings for this consumer instance as array
     *
     * @return string[]
     */
    public function getConfiguration(): array;

    /**
     * Queries the broker for metadata on a certain topic
     *
     * @param string $topicName
     * @param integer $timeoutMs
     * @return SkcMetadataTopic
     */
    public function getMetadataForTopic(string $topicName, int $timeoutMs = 10000): SkcMetadataTopic;

    /**
     * Get the earliest offset for a certain timestamp for topic partitions
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @param integer                       $timeoutMs
     * @return array|SkcTopicPartition[]
     */
    public function offsetsForTimes(array $topicPartitions, int $timeoutMs): array;

    /**
     * Queries the broker for the first offset of a given topic and partition
     *
     * @param string  $topic
     * @param integer $partition
     * @param integer $timeoutMs
     * @return integer
     */
    public function getFirstOffsetForTopicPartition(string $topic, int $partition, int $timeoutMs): int;

    /**
     * Queries the broker for the last offset of a given topic and partition
     *
     * @param string  $topic
     * @param integer $partition
     * @param integer $timeoutMs
     * @return integer
     */
    public function getLastOffsetForTopicPartition(string $topic, int $partition, int $timeoutMs): int;
}
