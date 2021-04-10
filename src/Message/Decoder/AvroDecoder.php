<?php

declare(strict_types=1);

namespace PhpKafka\Message\Decoder;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Exception\SchemaRegistryException;
use PhpKafka\Message\KafkaAvroSchemaInterface;
use PhpKafka\Message\KafkaConsumerMessage;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use PhpKafka\Message\Registry\AvroSchemaRegistryInterface;

final class AvroDecoder implements AvroDecoderInterface
{
    /**
     * @var AvroSchemaRegistryInterface
     */
    private $registry;

    /**
     * @var RecordSerializer
     */
    private $recordSerializer;

    /**
     * @param AvroSchemaRegistryInterface $registry
     * @param RecordSerializer            $recordSerializer
     */
    public function __construct(
        AvroSchemaRegistryInterface $registry,
        RecordSerializer $recordSerializer
    ) {
        $this->recordSerializer = $recordSerializer;
        $this->registry = $registry;
    }

    /**
     * @param KafkaConsumerMessageInterface $consumerMessage
     * @return KafkaConsumerMessageInterface
     * @throws SchemaRegistryException
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        return new KafkaConsumerMessage(
            $consumerMessage->getTopicName(),
            $consumerMessage->getPartition(),
            $consumerMessage->getOffset(),
            $consumerMessage->getTimestamp(),
            $this->decodeKey($consumerMessage),
            $this->decodeBody($consumerMessage),
            $consumerMessage->getHeaders()
        );
    }

    /**
     * @param KafkaConsumerMessageInterface $consumerMessage
     * @return mixed
     * @throws SchemaRegistryException
     */
    private function decodeBody(KafkaConsumerMessageInterface $consumerMessage)
    {
        $body = $consumerMessage->getBody();
        $topicName = $consumerMessage->getTopicName();

        if (null === $body) {
            return null;
        }

        if (false === $this->registry->hasBodySchemaForTopic($topicName)) {
            return $body;
        }

        $avroSchema = $this->registry->getBodySchemaForTopic($topicName);
        $schemaDefinition = $avroSchema->getDefinition();

        return $this->recordSerializer->decodeMessage($body, $schemaDefinition);
    }

    /**
     * @param KafkaConsumerMessageInterface $consumerMessage
     * @return mixed
     * @throws SchemaRegistryException
     */
    private function decodeKey(KafkaConsumerMessageInterface $consumerMessage)
    {
        $key = $consumerMessage->getKey();
        $topicName = $consumerMessage->getTopicName();

        if (null === $key) {
            return null;
        }

        if (false === $this->registry->hasKeySchemaForTopic($topicName)) {
            return $key;
        }

        $avroSchema = $this->registry->getKeySchemaForTopic($topicName);
        $schemaDefinition = $avroSchema->getDefinition();

        return $this->recordSerializer->decodeMessage($key, $schemaDefinition);
    }

    /**
     * @return AvroSchemaRegistryInterface
     */
    public function getRegistry(): AvroSchemaRegistryInterface
    {
        return $this->registry;
    }
}
