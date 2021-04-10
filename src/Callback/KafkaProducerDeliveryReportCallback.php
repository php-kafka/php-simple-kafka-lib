<?php

declare(strict_types=1);

namespace PhpKafka\Callback;

use PhpKafka\Exception\KafkaProducerException;
use SimpleKafkaClient\Producer as SkcProducer;
use SimpleKafkaClient\Message as SkcMessage;

// phpcs:disable
require_once __DIR__ . '/../Exception/KafkaProducerException.php';  // @codeCoverageIgnore
// phpcs:enable

final class KafkaProducerDeliveryReportCallback
{
    /**
     * @param SkcProducer $producer
     * @param SkcMessage  $message
     * @return void
     * @throws KafkaProducerException
     */
    public function __invoke(SkcProducer $producer, SkcMessage $message)
    {

        if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
            return;
        }

        throw new KafkaProducerException(
            $message->errstr(),
            $message->err
        );
    }
}
