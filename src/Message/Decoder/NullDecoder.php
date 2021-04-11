<?php

declare(strict_types=1);

namespace PhpKafka\Message\Decoder;

use PhpKafka\Message\KafkaConsumerMessageInterface;

final class NullDecoder implements DecoderInterface
{

    /**
     * @param KafkaConsumerMessageInterface $consumerMessage
     * @return KafkaConsumerMessageInterface
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        return $consumerMessage;
    }
}
