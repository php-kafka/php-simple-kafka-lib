<?php

declare(strict_types=1);

namespace PhpKafka\Exception;

class KafkaConsumerSubscriptionException extends \Exception
{
    public const MIXED_SUBSCRIPTION_EXCEPTION_MESSAGE
        = 'Dont mix subscriptions and assignments (with and without partitions defined).';
}
