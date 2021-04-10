<?php

namespace PhpKafka\Tests\Unit\Kafka\Callback;

use PhpKafka\Callback\KafkaConsumerRebalanceCallback;
use PhpKafka\Exception\KafkaRebalanceException;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleKafkaClient\Exception as SkcException;
use SimpleKafkaClient\Consumer as SkcConsumer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Callback\KafkaConsumerRebalanceCallback
 */
class KafkaConsumerRebalanceCallbackTest extends TestCase
{
    public function testInvokeWithError()
    {
        $exceptionMessage = 'Foo';
        $exceptionCode = 10;

        self::expectException(KafkaRebalanceException::class);
        self::expectExceptionMessage($exceptionMessage);
        self::expectExceptionCode($exceptionCode);

        $consumer = $this->getConsumerMock();

        $consumer
            ->expects(self::once())
            ->method('assign')
            ->with(null)
            ->willThrowException(new SkcException($exceptionMessage, $exceptionCode));

        call_user_func(new KafkaConsumerRebalanceCallback(), $consumer, 1, []);
    }

    public function testInvokeAssign()
    {
        $partitions = [1, 2, 3];

        $consumer = $this->getConsumerMock();

        $consumer
            ->expects(self::once())
            ->method('assign')
            ->with($partitions)
            ->willReturn(null);


        call_user_func(
            new KafkaConsumerRebalanceCallback(),
            $consumer,
            RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS,
            $partitions
        );
    }

    public function testInvokeRevoke()
    {
        $consumer = $this->getConsumerMock();

        $consumer
            ->expects(self::once())
            ->method('assign')
            ->with(null)
            ->willReturn(null);

        call_user_func(new KafkaConsumerRebalanceCallback(), $consumer, RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS);
    }

    /**
     * @return MockObject|SkcConsumer
     */
    private function getConsumerMock()
    {
        //create mock to assign topics
        $consumerMock = $this->getMockBuilder(SkcConsumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assign', 'unsubscribe', 'getSubscription'])
            ->getMock();

        $consumerMock
            ->expects(self::any())
            ->method('unsubscribe')
            ->willReturn(null);

        $consumerMock
            ->expects(self::any())
            ->method('getSubscription')
            ->willReturn([]);

        return $consumerMock;
    }
}
