<?php

declare(strict_types=1);

namespace PhpKafka\Message;

interface KafkaConsumerMessageInterface extends KafkaMessageInterface
{

    /**
     * @return integer
     */
    public function getOffset(): int;

    /**
     * @return integer
     */
    public function getTimestamp(): int;
}