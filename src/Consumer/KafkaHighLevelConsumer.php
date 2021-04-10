<?php

declare(strict_types=1);

namespace PhpKafka\Consumer;

use PhpKafka\Configuration\KafkaConfiguration;
use PhpKafka\Exception\KafkaConsumerAssignmentException;
use PhpKafka\Exception\KafkaConsumerCommitException;
use PhpKafka\Exception\KafkaConsumerRequestException;
use PhpKafka\Exception\KafkaConsumerSubscriptionException;
use PhpKafka\Message\Decoder\DecoderInterface;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use SimpleKafkaClient\Exception as SkcException;
use SimpleKafkaClient\Message as SkcMessage;
use SimpleKafkaClient\TopicPartition;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;
use SimpleKafkaClient\Consumer as SkcConsumer;

final class KafkaHighLevelConsumer extends AbstractKafkaConsumer implements KafkaHighLevelConsumerInterface
{

    /**
     * @var SkcConsumer
     */
    protected $consumer;

    /**
     * @param SkcConsumer $consumer
     * @param KafkaConfiguration       $kafkaConfiguration
     * @param DecoderInterface         $decoder
     */
    public function __construct(
        SkcConsumer $consumer,
        KafkaConfiguration $kafkaConfiguration,
        DecoderInterface $decoder
    ) {
        parent::__construct($consumer, $kafkaConfiguration, $decoder);
    }

    /**
     * Subscribes to all defined topics, if no partitions were set, subscribes to all partitions.
     * If partition(s) (and optionally offset(s)) were set, subscribes accordingly
     *
     * @param array<TopicSubscription> $topicSubscriptions
     * @throws KafkaConsumerSubscriptionException
     * @return void
     */
    public function subscribe(array $topicSubscriptions = []): void
    {
        $subscriptions = $this->getTopicSubscriptions($topicSubscriptions);
        $assignments = $this->getTopicAssignments($topicSubscriptions);

        if ([] !== $subscriptions && [] !== $assignments) {
            throw new KafkaConsumerSubscriptionException(
                KafkaConsumerSubscriptionException::MIXED_SUBSCRIPTION_EXCEPTION_MESSAGE
            );
        }

        try {
            if ([] !== $subscriptions) {
                $this->consumer->subscribe($subscriptions);
            } else {
                $this->consumer->assign($assignments);
            }
            $this->subscribed = true;
        } catch (SkcException $e) {
            throw new KafkaConsumerSubscriptionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Unsubscribes from the current subscription / assignment
     *
     * @throws KafkaConsumerSubscriptionException
     * @return void
     */
    public function unsubscribe(): void
    {
        try {
            $this->consumer->unsubscribe();
            $this->subscribed = false;
        } catch (SkcException $e) {
            throw new KafkaConsumerSubscriptionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Commits the offset to the broker for the given message(s)
     * This is a blocking function, checkout out commitAsync if you want to commit in a non blocking manner
     *
     * @param KafkaConsumerMessageInterface|KafkaConsumerMessageInterface[] $messages
     * @return void
     * @throws KafkaConsumerCommitException
     */
    public function commit($messages): void
    {
        $this->commitMessages($messages);
    }

    /**
     * Assigns a consumer to the given TopicPartition(s)
     *
     * @param SkcTopicPartition[] $topicPartitions
     * @throws KafkaConsumerAssignmentException
     * @return void
     */
    public function assign(array $topicPartitions): void
    {
        try {
            $this->consumer->assign($topicPartitions);
        } catch (SkcException $e) {
            throw new KafkaConsumerAssignmentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Asynchronous version of commit (non blocking)
     *
     * @param KafkaConsumerMessageInterface|KafkaConsumerMessageInterface[] $messages
     * @return void
     * @throws KafkaConsumerCommitException
     */
    public function commitAsync($messages): void
    {
        $this->commitMessages($messages, true);
    }

    /**
     * Gets the current assignment for the consumer
     *
     * @return array|SkcTopicPartition[]
     * @throws KafkaConsumerAssignmentException
     */
    public function getAssignment(): array
    {
        try {
            return $this->consumer->getAssignment();
        } catch (SkcException $e) {
            throw new KafkaConsumerAssignmentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Gets the commited offset for a TopicPartition for the configured consumer group
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @param integer                       $timeoutMs
     * @return array|SkcTopicPartition[]
     * @throws KafkaConsumerRequestException
     */
    public function getCommittedOffsets(array $topicPartitions, int $timeoutMs): array
    {
        try {
            return $this->consumer->getCommittedOffsets($topicPartitions, $timeoutMs);
        } catch (SkcException $e) {
            throw new KafkaConsumerRequestException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get current offset positions of the consumer
     *
     * @param array|SkcTopicPartition[] $topicPartitions
     * @return array|SkcTopicPartition[]
     */
    public function getOffsetPositions(array $topicPartitions): array
    {
        return $this->consumer->getOffsetPositions($topicPartitions);
    }

    /**
     * Close the consumer connection
     *
     * @return void;
     */
    public function close(): void
    {
        $this->consumer->close();
    }

    /**
     * @param integer $timeoutMs
     * @return SkcMessage|null
     * @throws SkcException
     */
    protected function kafkaConsume(int $timeoutMs): ?SkcMessage
    {
        return $this->consumer->consume($timeoutMs);
    }

    /**
     * @param KafkaConsumerMessageInterface|KafkaConsumerMessageInterface[] $messages
     * @param boolean                                                       $asAsync
     * @return void
     * @throws KafkaConsumerCommitException
     */
    private function commitMessages($messages, bool $asAsync = false): void
    {
        $messages = is_array($messages) ? $messages : [$messages];

        $offsetsToCommit = $this->getOffsetsToCommitForMessages($messages);

        try {
            if (true === $asAsync) {
                $this->consumer->commitAsync($offsetsToCommit);
            } else {
                $this->consumer->commit($offsetsToCommit);
            }
        } catch (SkcException $e) {
            throw new KafkaConsumerCommitException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param array|KafkaConsumerMessageInterface[] $messages
     * @return array|SkcTopicPartition[]
     */
    private function getOffsetsToCommitForMessages(array $messages): array
    {
        $offsetsToCommit = [];

        foreach ($messages as $message) {
            $topicPartition = sprintf('%s-%s', $message->getTopicName(), $message->getPartition());

            if (true === isset($offsetsToCommit[$topicPartition])) {
                if ($message->getOffset() + 1 > $offsetsToCommit[$topicPartition]->getOffset()) {
                    $offsetsToCommit[$topicPartition]->setOffset($message->getOffset() + 1);
                }
                continue;
            }

            $offsetsToCommit[$topicPartition] = new SkcTopicPartition(
                $message->getTopicName(),
                $message->getPartition(),
                $message->getOffset() + 1
            );
        }

        return $offsetsToCommit;
    }

    /**
     * @param array<TopicSubscription> $topicSubscriptions
     * @return array|string[]
     */
    private function getTopicSubscriptions(array $topicSubscriptions = []): array
    {
        $subscriptions = [];

        if ([] === $topicSubscriptions) {
            $topicSubscriptions = $this->kafkaConfiguration->getTopicSubscriptions();
        }

        foreach ($topicSubscriptions as $topicSubscription) {
            if (
                [] !== $topicSubscription->getPartitions()
                || KafkaConsumerBuilderInterface::OFFSET_STORED !== $topicSubscription->getOffset()
            ) {
                continue;
            }
            $subscriptions[] = $topicSubscription->getTopicName();
        }

        return $subscriptions;
    }

    /**
     * @param array<TopicSubscription> $topicSubscriptions
     * @return array|SkcTopicPartition[]
     */
    private function getTopicAssignments(array $topicSubscriptions = []): array
    {
        $assignments = [];

        if ([] === $topicSubscriptions) {
            $topicSubscriptions = $this->kafkaConfiguration->getTopicSubscriptions();
        }

        foreach ($topicSubscriptions as $topicSubscription) {
            if (
                [] === $topicSubscription->getPartitions()
                && KafkaConsumerBuilderInterface::OFFSET_STORED === $topicSubscription->getOffset()
            ) {
                continue;
            }

            $offset = $topicSubscription->getOffset();
            $partitions = $topicSubscription->getPartitions();

            if ([] === $partitions) {
                $partitions = $this->getAllTopicPartitions($topicSubscription->getTopicName());
            }

            foreach ($partitions as $partitionId) {
                $assignments[] = new SkcTopicPartition(
                    $topicSubscription->getTopicName(),
                    $partitionId,
                    $offset
                );
            }
        }

        return $assignments;
    }
}
