<?php

declare(strict_types=1);

namespace PhpKafka\Tests\Unit\Kafka\Message;

use PhpKafka\Message\KafkaConsumerMessage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Message\AbstractKafkaMessage
 * @covers \PhpKafka\Message\KafkaConsumerMessage
 */
final class KafkaConsumerMessageTest extends TestCase
{
    public function testMessageGettersAndConstructor()
    {
        $key = '1234-1234-1234';
        $body = 'foo bar baz';
        $topic = 'test';
        $offset = 42;
        $partition = 1;
        $timestamp = 1562324233704;
        $headers = [ 'key' => 'value' ];

        $message = new KafkaConsumerMessage(
            $topic,
            $partition,
            $offset,
            $timestamp,
            $key,
            $body,
            $headers
        );

        self::assertEquals($key, $message->getKey());
        self::assertEquals($body, $message->getBody());
        self::assertEquals($topic, $message->getTopicName());
        self::assertEquals($offset, $message->getOffset());
        self::assertEquals($partition, $message->getPartition());
        self::assertEquals($timestamp, $message->getTimestamp());
        self::assertEquals($headers, $message->getHeaders());
    }
}
