<?php

declare(strict_types=1);

namespace PhpKafka\Exception;

class KafkaProducerTransactionRetryException extends \Exception
{
    public const RETRIABLE_TRANSACTION_EXCEPTION_MESSAGE = 'Produce failed but can be retried (%s)';
}
