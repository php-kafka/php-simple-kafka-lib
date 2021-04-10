<?php

namespace PhpKafka\Tests\Unit\Kafka\Consumer;

use PhpKafka\Consumer\KafkaConsumer;
use PhpKafka\Exception\KafkaConsumerConsumeException;
use PhpKafka\Exception\KafkaConsumerEndOfPartitionException;
use PhpKafka\Exception\KafkaConsumerTimeoutException;
use PhpKafka\Message\Decoder\DecoderInterface;
use PhpKafka\Consumer\TopicSubscription;
use PhpKafka\Exception\KafkaConsumerAssignmentException;
use PhpKafka\Exception\KafkaConsumerRequestException;
use PhpKafka\Exception\KafkaConsumerSubscriptionException;
use PhpKafka\Exception\KafkaConsumerCommitException;
use PhpKafka\Configuration\KafkaConfiguration;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use PHPUnit\Framework\TestCase;
use SimpleKafkaClient\Consumer as SkcConsumer;
use SimpleKafkaClient\ConsumerTopic as SkcConsumerTopic;
use SimpleKafkaClient\Exception as SkcException;
use SimpleKafkaClient\Message as SkcMessage;
use SimpleKafkaClient\Metadata as SkcMetadata;
use SimpleKafkaClient\Metadata\Collection as SkcMetadataCollection;
use SimpleKafkaClient\Metadata\Partition as SkcMetadataPartition;
use SimpleKafkaClient\Metadata\Topic as SkcMetadataTopic;
use SimpleKafkaClient\TopicPartition as SkcTopicPartition;

/**
 * @covers \PhpKafka\Consumer\AbstractKafkaConsumer
 * @covers \PhpKafka\Consumer\KafkaConsumer
 */
final class KafkaConsumerTest extends TestCase
{

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeSuccess(): void
    {
        $topics = [new TopicSubscription('testTopic'), new TopicSubscription('testTopic2')];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturnOnConsecutiveCalls($topics, []);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('subscribe')->with(['testTopic', 'testTopic2']);

        $kafkaConsumer->subscribe();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeSuccessWithParam(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::never())->method('getTopicSubscriptions');
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('subscribe')->with(['testTopic3']);

        $kafkaConsumer->subscribe([new TopicSubscription('testTopic3')]);
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeSuccessWithAssignmentWithPartitions(): void
    {
        $topics = [new TopicSubscription('testTopic', [1,2], RD_KAFKA_OFFSET_BEGINNING)];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturnOnConsecutiveCalls([], $topics);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('assign');

        $kafkaConsumer->subscribe();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeSuccessWithAssignmentWithOffsetOnly(): void
    {
        $partitions = [
            $this->getMetadataPartitionMock(1),
            $this->getMetadataPartitionMock(2)
        ];

        /** @var SkcConsumerTopic|MockObject $rdKafkaConsumerTopicMock */
        $rdKafkaConsumerTopicMock = $this->createMock(SkcConsumerTopic::class);

        /** @var SkcMetadataTopic|MockObject $rdKafkaMetadataTopicMock */
        $rdKafkaMetadataTopicMock = $this->createMock(SkcMetadataTopic::class);
        $rdKafkaMetadataTopicMock
            ->expects(self::once())
            ->method('getPartitions')
            ->willReturn($partitions);

        /** @var SkcMetadata|MockObject $rdKafkaMetadataMock */
        $rdKafkaMetadataMock = $this->createMock(SkcMetadata::class);
        $rdKafkaMetadataMock
            ->expects(self::once())
            ->method('getTopics')
            ->willReturnCallback(
                function () use ($rdKafkaMetadataTopicMock) {
                    /** @var SkcMetadataCollection|MockObject $collection */
                    $collection = $this->createMock(SkcMetadataCollection::class);
                    $collection
                        ->expects(self::once())
                        ->method('current')
                        ->willReturn($rdKafkaMetadataTopicMock);

                    return $collection;
                }
            );

        $topics = [new TopicSubscription('testTopic', [], RD_KAFKA_OFFSET_END)];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturnOnConsecutiveCalls([], $topics);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('assign')->with(
            $this->callback(
                function (array $assignment) {
                    self::assertCount(2, $assignment);
                    return true;
                }
            )
        );
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getMetadata')
            ->with(false, 10000, $rdKafkaConsumerTopicMock)
            ->willReturn($rdKafkaMetadataMock);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getTopicHandle')
            ->with('testTopic')
            ->willReturn($rdKafkaConsumerTopicMock);


        $kafkaConsumer->subscribe();
    }


    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeFailureOnMixedSubscribe(): void
    {
        $topics = [
            new TopicSubscription('testTopic'),
            new TopicSubscription('anotherTestTopic', [1,2], RD_KAFKA_OFFSET_BEGINNING)
        ];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturn($topics);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::never())->method('subscribe');
        $rdKafkaConsumerMock->expects(self::never())->method('assign');


        $this->expectException(KafkaConsumerSubscriptionException::class);
        $this->expectExceptionMessage(KafkaConsumerSubscriptionException::MIXED_SUBSCRIPTION_EXCEPTION_MESSAGE);

        $kafkaConsumer->subscribe();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testSubscribeFailure(): void
    {
        $topics = [new TopicSubscription('testTopic')];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturn($topics);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('subscribe')
            ->with(['testTopic'])
            ->willThrowException(new SkcException('Error', 100));

        $this->expectException(KafkaConsumerSubscriptionException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('Error');

        $kafkaConsumer->subscribe();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testUnsubscribeSuccesss(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('unsubscribe');

        $kafkaConsumer->unsubscribe();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testUnsubscribeSuccesssConsumeFails(): void
    {
        self::expectException(KafkaConsumerConsumeException::class);
        self::expectExceptionMessage(KafkaConsumerConsumeException::NOT_SUBSCRIBED_EXCEPTION_MESSAGE);

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock->expects(self::once())->method('unsubscribe');

        $kafkaConsumer->unsubscribe();

        $kafkaConsumer->consume();
    }

    /**
     * @throws KafkaConsumerSubscriptionException
     */
    public function testUnsubscribeFailure(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('unsubscribe')
            ->willThrowException(new SkcException('Error', 100));

        $this->expectException(KafkaConsumerSubscriptionException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('Error');


        $kafkaConsumer->unsubscribe();
    }

    /**
     * @throws KafkaConsumerCommitException
     */
    public function testCommitSuccesss(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message->expects(self::exactly(1))->method('getOffset')->willReturn(0);
        $message->expects(self::exactly(1))->method('getTopicName')->willReturn('test');
        $message->expects(self::exactly(1))->method('getPartition')->willReturn(1);
        $message2 = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message2->expects(self::exactly(1))->method('getOffset')->willReturn(1);
        $message2->expects(self::exactly(2))->method('getTopicName')->willReturn('test');
        $message2->expects(self::exactly(2))->method('getPartition')->willReturn(1);
        $message3 = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message3->expects(self::exactly(2))->method('getOffset')->willReturn(2);
        $message3->expects(self::exactly(1))->method('getTopicName')->willReturn('test');
        $message3->expects(self::exactly(1))->method('getPartition')->willReturn(1);
        $message4 = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message4->expects(self::exactly(1))->method('getOffset')->willReturn(0);
        $message4->expects(self::exactly(2))->method('getTopicName')->willReturn('test');
        $message4->expects(self::exactly(2))->method('getPartition')->willReturn(2);


        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $rdKafkaConsumerMock->expects(self::once())->method('commit')->with(
            $this->callback(
                function (array $topicPartitions) {
                    self::assertCount(2, $topicPartitions);
                    self::assertInstanceOf(SkcTopicPartition::class, $topicPartitions['test-1']);
                    self::assertInstanceOf(SkcTopicPartition::class, $topicPartitions['test-2']);
                    self::assertEquals(3, $topicPartitions['test-1']->getOffset());
                    self::assertEquals(1, $topicPartitions['test-2']->getOffset());

                    return true;
                }
            )
        );

        $kafkaConsumer->commit([$message2, $message, $message3, $message4]);
    }

    /**
     * @throws KafkaConsumerCommitException
     */
    public function testCommitSingleSuccesss(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message->expects(self::exactly(1))->method('getOffset')->willReturn(0);
        $message->expects(self::exactly(2))->method('getTopicName')->willReturn('test');
        $message->expects(self::exactly(2))->method('getPartition')->willReturn(1);


        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $rdKafkaConsumerMock->expects(self::once())->method('commit')->with(
            $this->callback(
                function (array $topicPartitions) {
                    self::assertCount(1, $topicPartitions);
                    self::assertInstanceOf(SkcTopicPartition::class, $topicPartitions['test-1']);
                    self::assertEquals(1, $topicPartitions['test-1']->getOffset());
                    return true;
                }
            )
        );

        $kafkaConsumer->commit($message);
    }

    /**
     * @throws KafkaConsumerCommitException
     */
    public function testCommitAsyncSuccesss(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $message = $this->createMock(KafkaConsumerMessageInterface::class);

        $rdKafkaConsumerMock->expects(self::once())->method('commitAsync');

        $kafkaConsumer->commitAsync([$message]);
    }

    /**
     * @throws KafkaConsumerCommitException
     */
    public function testCommitFails(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $message = $this->createMock(KafkaConsumerMessageInterface::class);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('commit')
            ->willThrowException(new SkcException('Failure', 99));

        $this->expectException(KafkaConsumerCommitException::class);
        $this->expectExceptionCode(99);
        $this->expectExceptionMessage('Failure');

        $kafkaConsumer->commit([$message]);
    }

    /**
     * @throws KafkaConsumerAssignmentException
     */
    public function testAssignSuccess(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $topicPartitions = ['test'];

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('assign')
            ->with($topicPartitions);

        $kafkaConsumer->assign($topicPartitions);
    }

    /**
     * @throws KafkaConsumerAssignmentException
     */
    public function testAssignFail(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $topicPartitions = ['test'];

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('assign')
            ->with($topicPartitions)
            ->willThrowException(new SkcException('Failure', 99));

        $this->expectException(KafkaConsumerAssignmentException::class);
        $this->expectExceptionCode(99);
        $this->expectExceptionMessage('Failure');

        $kafkaConsumer->assign($topicPartitions);
    }

    /**
     * @throws KafkaConsumerAssignmentException
     */
    public function testGetAssignment(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $topicPartitions = ['test'];

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getAssignment')
            ->willReturn($topicPartitions);

        $this->assertEquals($topicPartitions, $kafkaConsumer->getAssignment());
    }

    /**
     * @throws KafkaConsumerAssignmentException
     */
    public function testGetAssignmentException(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getAssignment')
            ->willThrowException(new SkcException('Fail', 99));

        $this->expectException(KafkaConsumerAssignmentException::class);
        $this->expectExceptionCode(99);
        $this->expectExceptionMessage('Fail');
        $kafkaConsumer->getAssignment();
    }

    public function testKafkaConsumeWithDecode(): void
    {
        $message = new SkcMessage();
        $message->key = 'test';
        $message->payload = null;
        $message->topic_name = 'test_topic';
        $message->partition = '9';
        $message->offset = '501';
        $message->timestamp = '500';
        $message->headers = 'header';
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;

        $topics = [new TopicSubscription('testTopic')];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('subscribe')
            ->with(['testTopic']);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(10000)
            ->willReturn($message);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturnOnConsecutiveCalls($topics, []);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $decoderMock->expects(self::once())->method('decode')->with(
            $this->callback(
                function (KafkaConsumerMessageInterface $message) {
                    self::assertEquals('test', $message->getKey());
                    self::assertNull($message->getBody());
                    self::assertEquals('test_topic', $message->getTopicName());
                    self::assertEquals(9, $message->getPartition());
                    self::assertEquals(501, $message->getOffset());
                    self::assertEquals(500, $message->getTimestamp());
                    self::assertEquals(['header'], $message->getHeaders());

                    return true;
                }
            )
        );
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume();
    }

    public function testKafkaConsumeWithoutDecode(): void
    {
        $message = new SkcMessage();
        $message->key = 'test';
        $message->payload = null;
        $message->topic_name = 'test_topic';
        $message->partition = 9;
        $message->offset = 501;
        $message->timestamp = 500;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;

        $topics = [new TopicSubscription('testTopic')];
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('subscribe')
            ->with(['testTopic']);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(10000)
            ->willReturn($message);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::exactly(2))->method('getTopicSubscriptions')->willReturnOnConsecutiveCalls($topics, []);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $decoderMock->expects(self::never())->method('decode');
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume(10000, false);
    }

    public function testDecodeMessage(): void
    {
        $messageMock = $this->createMock(KafkaConsumerMessageInterface::class);
        $messageMock->expects(self::once())->method('getKey')->willReturn('test');
        $messageMock->expects(self::once())->method('getBody')->willReturn('some body');
        $messageMock->expects(self::once())->method('getTopicName')->willReturn('test_topic');
        $messageMock->expects(self::once())->method('getPartition')->willReturn(9);
        $messageMock->expects(self::once())->method('getOffset')->willReturn(501);
        $messageMock->expects(self::once())->method('getTimestamp')->willReturn(500);
        $messageMock->expects(self::once())->method('getHeaders')->willReturn(['some' => 'header']);

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $decoderMock->expects(self::once())->method('decode')->with(
            $this->callback(
                function (KafkaConsumerMessageInterface $message) {
                    self::assertEquals('test', $message->getKey());
                    self::assertEquals('some body', $message->getBody());
                    self::assertEquals('test_topic', $message->getTopicName());
                    self::assertEquals(9, $message->getPartition());
                    self::assertEquals(501, $message->getOffset());
                    self::assertEquals(500, $message->getTimestamp());
                    self::assertEquals(['some' => 'header'], $message->getHeaders());
                    return true;
                }
            )
        );
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $kafkaConsumer->decodeMessage($messageMock);
    }

    /**
     * @throws KafkaConsumerRequestException
     */
    public function testGetCommittedOffsets(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $committedOffsets = ['test'];

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getCommittedOffsets')
            ->with($committedOffsets, 1)
            ->willReturn($committedOffsets);

        $this->assertEquals($committedOffsets, $kafkaConsumer->getCommittedOffsets($committedOffsets, 1));
    }

    /**
     * @throws KafkaConsumerRequestException
     */
    public function testGetCommittedOffsetsException(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getCommittedOffsets')
            ->willThrowException(new SkcException('Fail', 99));

        $this->expectException(KafkaConsumerRequestException::class);
        $this->expectExceptionCode(99);
        $this->expectExceptionMessage('Fail');
        $kafkaConsumer->getCommittedOffsets([], 1);
    }

    /**
     * @return void
     */
    public function testGetOffsetPositions(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('getOffsetPositions')
            ->with([])
            ->willReturn([]);

        $kafkaConsumer->getOffsetPositions([]);
    }

    /**
     * @return void
     */
    public function testClose(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);
        $rdKafkaConsumerMock->expects(self::once())->method('close');

        $kafkaConsumer->close();
    }

    /**
     * @param int $partitionId
     * @return SkcMetadataPartition|MockObject
     */
    private function getMetadataPartitionMock(int $partitionId): SkcMetadataPartition
    {
        $partitionMock = $this->getMockBuilder(SkcMetadataPartition::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $partitionMock
            ->expects(self::once())
            ->method('getId')
            ->willReturn($partitionId);

        return $partitionMock;
    }

    /**
     * @return void
     */
    public function testOffsetsForTimes(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('offsetsForTimes')
            ->with([], 1000)
            ->willReturn([]);

        $kafkaConsumer->offsetsForTimes([], 1000);
    }

    /**
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $kafkaConfigurationMock->expects(self::any())->method('dump')->willReturn([]);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        self::assertIsArray($kafkaConsumer->getConfiguration());
    }

    /**
     * @return void
     */
    public function testGetFirstOffsetForTopicPartition(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('queryWatermarkOffsets')
            ->with('test-topic', 1, 0, 0, 1000)
            ->willReturnCallback(
                function (string $topic, int $partition, int &$lowOffset, int &$highOffset, int $timeoutMs) {
                    $lowOffset++;
                }
            );

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $lowOffset = $kafkaConsumer->getFirstOffsetForTopicPartition('test-topic', 1, 1000);

        self::assertEquals(1, $lowOffset);
    }

    /**
     * @return void
     */
    public function testGetLastOffsetForTopicPartition(): void
    {
        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('queryWatermarkOffsets')
            ->with('test-topic', 1, 0, 0, 1000)
            ->willReturnCallback(
                function (string $topic, int $partition, int &$lowOffset, int &$highOffset, int $timeoutMs) {
                    $highOffset += 5;
                }
            );

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $lowOffset = $kafkaConsumer->getLastOffsetForTopicPartition('test-topic', 1, 1000);

        $this->assertEquals(5, $lowOffset);
    }

    /**
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerSubscriptionException
     * @throws KafkaConsumerTimeoutException
     * @return void
     */
    public function testConsumeThrowsEofExceptionIfQueueConsumeReturnsNull(): void
    {
        self::expectException(KafkaConsumerEndOfPartitionException::class);
        self::expectExceptionCode(RD_KAFKA_RESP_ERR__PARTITION_EOF);
        self::expectExceptionMessage(kafka_err2str(RD_KAFKA_RESP_ERR__PARTITION_EOF));

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(10000)
            ->willReturn(null);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume();
    }

    /**
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerSubscriptionException
     * @throws KafkaConsumerTimeoutException
     * @return void
     */
    public function testConsumeDedicatedEofException(): void
    {
        self::expectException(KafkaConsumerEndOfPartitionException::class);
        self::expectExceptionCode(RD_KAFKA_RESP_ERR__PARTITION_EOF);
        self::expectExceptionMessage(kafka_err2str(RD_KAFKA_RESP_ERR__PARTITION_EOF));

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $message = new SkcMessage();
        $message->err = RD_KAFKA_RESP_ERR__PARTITION_EOF;

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(10000)
            ->willReturn($message);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume();
    }

    /**
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerSubscriptionException
     * @throws KafkaConsumerTimeoutException
     * @return void
     */
    public function testConsumeDedicatedTimeoutException(): void
    {
        self::expectException(KafkaConsumerTimeoutException::class);
        self::expectExceptionCode(RD_KAFKA_RESP_ERR__TIMED_OUT);
        self::expectExceptionMessage(kafka_err2str(RD_KAFKA_RESP_ERR__TIMED_OUT));

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $message = new SkcMessage();
        $message->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume(1000);
    }

    /**
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerSubscriptionException
     * @throws KafkaConsumerTimeoutException
     * @return void
     */
    public function testConsumeThrowsExceptionIfConsumedMessageHasNoTopicAndErrorCodeIsNotOkay(): void
    {
        self::expectException(KafkaConsumerConsumeException::class);
        self::expectExceptionMessage('Unknown error');

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        /** @var SkcMessage|MockObject $rdKafkaMessageMock */
        $rdKafkaMessageMock = $this->createMock(SkcMessage::class);
        $rdKafkaMessageMock->err = RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN;
        $rdKafkaMessageMock->partition = 1;
        $rdKafkaMessageMock->offset = 103;
        $rdKafkaMessageMock->topic_name = null;
        $rdKafkaMessageMock
            ->expects(self::once())
            ->method('getErrorString')
            ->willReturn('Unknown error');

        $topicSubscription = new TopicSubscription('test-topic', [1], 103);

        $rdKafkaConsumerMock
            ->expects(self::once())
            ->method('consume')
            ->with(10000)
            ->willReturn($rdKafkaMessageMock);
        $kafkaConfigurationMock
            ->expects(self::exactly(2))
            ->method('getTopicSubscriptions')
            ->willReturn([$topicSubscription]);

        $kafkaConsumer->subscribe();
        $kafkaConsumer->consume();
    }

    /**
     * @throws KafkaConsumerConsumeException
     * @throws KafkaConsumerEndOfPartitionException
     * @throws KafkaConsumerTimeoutException
     * @return void
     */
    public function testConsumeThrowsExceptionIfConsumerIsCurrentlyNotSubscribed(): void
    {
        self::expectException(KafkaConsumerConsumeException::class);
        self::expectExceptionMessage('This consumer is currently not subscribed');

        $rdKafkaConsumerMock = $this->createMock(SkcConsumer::class);
        $kafkaConfigurationMock = $this->createMock(KafkaConfiguration::class);
        $decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);

        $kafkaConsumer = new KafkaConsumer($rdKafkaConsumerMock, $kafkaConfigurationMock, $decoderMock);

        $kafkaConsumer->consume();
    }
}
