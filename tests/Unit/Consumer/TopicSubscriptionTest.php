<?php

declare(strict_types=1);

namespace PhpKafka\Tests\Unit\Kafka\Consumer;

use PhpKafka\Consumer\TopicSubscription;
use PHPUnit\Framework\TestCase;
use SimpleKafkaClient\TopicConf;

/**
 * @covers \PhpKafka\Consumer\TopicSubscription
 */
final class TopicSubscriptionTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $topicName = 'test';
        $partitions = [1, 2];
        $offset = 1;
        $newPartitions = [2, 3];

        $topicSubscription = new TopicSubscription($topicName, $partitions, $offset);

        self::assertEquals($topicName, $topicSubscription->getTopicName());
        self::assertEquals($partitions, $topicSubscription->getPartitions());
        self::assertEquals($offset, $topicSubscription->getOffset());

        $topicSubscription->setPartitions($newPartitions);

        self::assertEquals($newPartitions, $topicSubscription->getPartitions());
    }
}
