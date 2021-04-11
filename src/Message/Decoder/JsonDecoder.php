<?php

declare(strict_types=1);

namespace PhpKafka\Message\Decoder;

use PhpKafka\Message\KafkaConsumerMessage;
use PhpKafka\Message\KafkaConsumerMessageInterface;

class JsonDecoder implements DecoderInterface
{

    /**
     * @param KafkaConsumerMessageInterface $consumerMessage
     * @return KafkaConsumerMessageInterface
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        $body = json_decode($consumerMessage->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return new KafkaConsumerMessage(
            $consumerMessage->getTopicName(),
            $consumerMessage->getPartition(),
            $consumerMessage->getOffset(),
            $consumerMessage->getTimestamp(),
            $consumerMessage->getKey(),
            $body,
            $consumerMessage->getHeaders()
        );
    }
}
