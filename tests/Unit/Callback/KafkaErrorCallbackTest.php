<?php

namespace PhpKafka\Tests\Unit\Kafka\Callback;

use PHPUnit\Framework\TestCase;
use SimpleKafkaClient\Consumer as SkcConsumer;
use PhpKafka\Callback\KafkaErrorCallback;

/**
 * @covers \PhpKafka\Callback\KafkaErrorCallback
 */
class KafkaErrorCallbackTest extends TestCase
{
    public function testInvokeWithBrokerException()
    {
        self::expectException('PhpKafka\Exception\KafkaBrokerException');
        $callback = new KafkaErrorCallback();
        call_user_func($callback, null, RD_KAFKA_RESP_ERR__FATAL, 'error');
    }

    public function testInvokeWithAcceptableError()
    {
        $callback = new KafkaErrorCallback();
        $result = call_user_func($callback, null, RD_KAFKA_RESP_ERR__TRANSPORT, 'error');

        self::assertNull($result);
    }
}
