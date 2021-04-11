<?php

declare(strict_types=1);

namespace PhpKafka\Consumer;

use PhpKafka\Exception\KafkaConsumerEndOfPartitionException;
use PhpKafka\Exception\KafkaConsumerTimeoutException;
use PhpKafka\Message\Decoder\DecoderInterface;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use PhpKafka\Configuration\KafkaConfiguration;
use PhpKafka\Exception\KafkaConsumerConsumeException;
use PhpKafka\Message\KafkaConsumerMessage;
use SimpleKafkaClient\Exception as SkcException;
use SimpleKafkaClient\Consumer as SkcConsumer;
use SimpleKafkaClient\ConsumerTopic as SkcConsumerTopic;
use SimpleKafkaClient\Metadata\Topic as SkcMetadataTopic;
use SimpleKafkaClient\Message as SkcMessage;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;

abstract class AbstractKafkaConsumer implements KafkaConsumerInterface
{

    /**
     * @var KafkaConfiguration
     */
    protected $kafkaConfiguration;

    /**
     * @var boolean
     */
    protected $subscribed = false;

    /**
     * @var SkcConsumer
     */
    protected $consumer;

    /**
     * @var DecoderInterface
     */
    protected $decoder;

    /**
     * @param mixed              $consumer
     * @param KafkaConfiguration $kafkaConfiguration
     * @param DecoderInterface   $decoder
     */
    public function __construct(
        $consumer,
        KafkaConfiguration $kafkaConfiguration,
        DecoderInterface $decoder
    ) {
        $this->consumer = $consumer;
        $this->kafkaConfiguration = $kafkaConfiguration;
        $this->decoder = $decoder;
    }

    /**
     * Returns true if the consumer has subscribed to its topics, otherwise false
     * It is mandatory to call `subscribe` before `consume`
     *
     * @return boolean
     */
    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    /**
     * Returns the configuration settings for this consumer instance as array
     *
     * @return string[]
     */
    public function getConfiguration(): array
    {
        return $this->kafkaConfiguration->dump();
    }

    /**
     * Consumes a message and returns it
     * In cases of errors / timeouts an exception is thrown
     *
     * @param integer $timeoutMs
     * @param boolean $autoDecode
     * @return KafkaConsumerMessageInterface
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerTimeoutException
     */
    public function consume(int $timeoutMs = 10000, bool $autoDecode = true): KafkaConsumerMessageInterface
    {
        if (false === $this->isSubscribed()) {
            throw new KafkaConsumerConsumeException(KafkaConsumerConsumeException::NOT_SUBSCRIBED_EXCEPTION_MESSAGE);
        }

        $rdKafkaMessage = $this->kafkaConsume($timeoutMs);

        if (RD_KAFKA_RESP_ERR__PARTITION_EOF === $rdKafkaMessage->err) {
            throw new KafkaConsumerEndOfPartitionException($rdKafkaMessage->getErrorString(), $rdKafkaMessage->err);
        } elseif (RD_KAFKA_RESP_ERR__TIMED_OUT === $rdKafkaMessage->err) {
            throw new KafkaConsumerTimeoutException($rdKafkaMessage->getErrorString(), $rdKafkaMessage->err);
        }

        $message = $this->getConsumerMessage($rdKafkaMessage);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $rdKafkaMessage->err) {
            throw new KafkaConsumerConsumeException($rdKafkaMessage->getErrorString(), $rdKafkaMessage->err, $message);
        }

        if (true === $autoDecode) {
            return $this->decoder->decode($message);
        }

        return $message;
    }

    /**
     * Decode consumer message
     *
     * @param KafkaConsumerMessageInterface $message
     * @return KafkaConsumerMessageInterface
     */
    public function decodeMessage(KafkaConsumerMessageInterface $message): KafkaConsumerMessageInterface
    {
        return $this->decoder->decode($message);
    }

    /**
     * Queries the broker for metadata on a certain topic
     *
     * @param string $topicName
     * @param integer $timeoutMs
     * @return SkcMetadataTopic
     * @throws SkcException
     */
    public function getMetadataForTopic(string $topicName, int $timeoutMs = 10000): SkcMetadataTopic
    {
        /** @var SkcConsumerTopic $topic */
        $topic = $this->consumer->getTopicHandle($topicName);
        return $this->consumer
            ->getMetadata(
                false,
                $timeoutMs,
                $topic
            )
            ->getTopics()
            ->current();
    }

    /**
     * Get the earliest offset for a certain timestamp for topic partitions
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @param integer                       $timeoutMs
     * @return array|SkcTopicPartition[]
     */
    public function offsetsForTimes(array $topicPartitions, int $timeoutMs): array
    {
        return $this->consumer->offsetsForTimes($topicPartitions, $timeoutMs);
    }

    /**
     * Queries the broker for the first offset of a given topic and partition
     *
     * @param string  $topic
     * @param integer $partition
     * @param integer $timeoutMs
     * @return integer
     */
    public function getFirstOffsetForTopicPartition(string $topic, int $partition, int $timeoutMs): int
    {
        $lowOffset = 0;
        $highOffset = 0;

        $this->consumer->queryWatermarkOffsets($topic, $partition, $lowOffset, $highOffset, $timeoutMs);

        return $lowOffset;
    }

    /**
     * Queries the broker for the last offset of a given topic and partition
     *
     * @param string  $topic
     * @param integer $partition
     * @param integer $timeoutMs
     * @return integer
     */
    public function getLastOffsetForTopicPartition(string $topic, int $partition, int $timeoutMs): int
    {
        $lowOffset = 0;
        $highOffset = 0;

        $this->consumer->queryWatermarkOffsets($topic, $partition, $lowOffset, $highOffset, $timeoutMs);

        return $highOffset;
    }

    /**
     * @param string $topic
     * @return int[]
     * @throws SkcException
     */
    protected function getAllTopicPartitions(string $topic): array
    {

        $partitions = [];
        $topicMetadata = $this->getMetadataForTopic($topic);
        $metaPartitions = $topicMetadata->getPartitions();

        while ($metaPartitions->valid()) {
            $partitions[] = $metaPartitions->current()->getId();
            $metaPartitions->next();
        }

        return $partitions;
    }

    /**
     * @param SkcMessage $message
     * @return KafkaConsumerMessageInterface
     */
    private function getConsumerMessage(SkcMessage $message): KafkaConsumerMessageInterface
    {
        return new KafkaConsumerMessage(
            (string) $message->topic_name,
            (int) $message->partition,
            (int) $message->offset,
            (int) $message->timestamp,
            $message->key,
            $message->payload,
            (array) $message->headers
        );
    }

    /**
     * @param integer $timeoutMs
     * @return SkcMessage
     */
    abstract protected function kafkaConsume(int $timeoutMs): SkcMessage;
}
