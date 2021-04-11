<?php

declare(strict_types=1);

namespace PhpKafka\Callback;

use SimpleKafkaClient\Consumer as SkcConsumer;
use PhpKafka\Exception\KafkaRebalanceException;
use SimpleKafkaClient\Exception as SkcException;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;

// phpcs:disable
require_once __DIR__ . '/../Exception/KafkaRebalanceException.php'; // @codeCoverageIgnore
// phpcs:enable

final class KafkaConsumerRebalanceCallback
{

    /**
     * @param SkcConsumer $consumer
     * @param integer         $errorCode
     * @param array|SkcTopicPartition[]|null      $partitions
     * @throws KafkaRebalanceException
     * @return void
     */
    public function __invoke(SkcConsumer $consumer, int $errorCode, array $partitions = null)
    {
        try {
            switch ($errorCode) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $consumer->assign($partitions);
                    break;

                default:
                    $consumer->assign(null);
                    break;
            }
        } catch (SkcException $e) {
            throw new KafkaRebalanceException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
