<?php

namespace PhpKafka\Tests\Unit\Kafka\Consumer;

use PhpKafka\Consumer\KafkaConsumer;
use PhpKafka\Consumer\KafkaConsumerInterface;
use PhpKafka\Consumer\KafkaConsumerBuilder;
use PhpKafka\Message\Decoder\DecoderInterface;
use PhpKafka\Consumer\TopicSubscription;
use PhpKafka\Exception\KafkaConsumerBuilderException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Consumer\KafkaConsumerBuilder
 */
final class KafkaConsumerBuilderTest extends TestCase
{

    /** @var KafkaConsumerBuilder */
    private $kafkaConsumerBuilder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->kafkaConsumerBuilder = KafkaConsumerBuilder::create();
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        self::assertInstanceOf(KafkaConsumerBuilder::class, KafkaConsumerBuilder::create());
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testAddBroker(): void
    {
        self::assertNotSame(
            $this->kafkaConsumerBuilder,
            $clone = $this->kafkaConsumerBuilder->withAdditionalBroker('localhost')
        );

        $reflectionProperty = new \ReflectionProperty($clone, 'brokers');
        $reflectionProperty->setAccessible(true);

        self::assertSame(['localhost'], $reflectionProperty->getValue($clone));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSubscribeToTopic(): void
    {
        self::assertNotSame(
            $this->kafkaConsumerBuilder,
            $clone = $this->kafkaConsumerBuilder->withAdditionalSubscription('test-topic')
        );

        $reflectionProperty = new \ReflectionProperty($clone, 'topics');
        $reflectionProperty->setAccessible(true);

        self::isInstanceOf(TopicSubscription::class, $reflectionProperty->getValue($clone));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testReplaceSubscribedToTopics(): void
    {
        self::assertNotSame(
            $this->kafkaConsumerBuilder,
            $clone = $this->kafkaConsumerBuilder->withSubscription('new-topic')
        );

        $reflectionProperty = new \ReflectionProperty($clone, 'topics');
        $reflectionProperty->setAccessible(true);

        $topicSubscription = $reflectionProperty->getValue($clone);
        self::assertCount(1, $topicSubscription);
        self::isInstanceOf(TopicSubscription::class, $topicSubscription[0]);
        self::assertSame('new-topic', $topicSubscription[0]->getTopicName());
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testAddConfig(): void
    {
        $intialConfig = ['group.id' => 'test-group', 'enable.auto.offset.store' => true];
        $newConfig = ['offset.store.sync.interval.ms' => 60e3];
        $clone = $this->kafkaConsumerBuilder->withAdditionalConfig($intialConfig);
        $clone = $clone->withAdditionalConfig($newConfig);

        $reflectionProperty = new \ReflectionProperty($clone, 'config');
        $reflectionProperty->setAccessible(true);

        self::assertSame(
            [
                'offset.store.sync.interval.ms' => 60e3,
                'group.id' => 'test-group',
                'enable.auto.offset.store' => true,
                'enable.auto.commit' => false,
                'enable.partition.eof' => true,
                'auto.offset.reset' => 'earliest'
            ],
            $reflectionProperty->getValue($clone)
        );
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetDecoder(): void
    {
        $decoder = $this->getMockForAbstractClass(DecoderInterface::class);

        $clone = $this->kafkaConsumerBuilder->withDecoder($decoder);

        $reflectionProperty = new \ReflectionProperty($clone, 'decoder');
        $reflectionProperty->setAccessible(true);

        self::assertInstanceOf(DecoderInterface::class, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetConsumerGroup(): void
    {
        $clone = $this->kafkaConsumerBuilder->withConsumerGroup('test-consumer');

        $reflectionProperty = new \ReflectionProperty($clone, 'consumerGroup');
        $reflectionProperty->setAccessible(true);

        self::assertSame('test-consumer', $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetErrorCallback(): void
    {
        $callback = function () {
            // Anonymous test method, no logic required
        };

        $clone = $this->kafkaConsumerBuilder->withErrorCallback($callback);

        $reflectionProperty = new \ReflectionProperty($clone, 'errorCallback');
        $reflectionProperty->setAccessible(true);

        self::assertSame($callback, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);

        $consumer = $clone
            ->withAdditionalBroker('localhost')
            ->withSubscription('test')
            ->withLogCallback($callback)
            ->build();
        $conf = $consumer->getConfiguration();
        self::assertArrayHasKey('error_cb', $conf);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetRebalanceCallback(): void
    {
        $callback = function () {
            // Anonymous test method, no logic required
        };

        $clone = $this->kafkaConsumerBuilder->withRebalanceCallback($callback);

        $reflectionProperty = new \ReflectionProperty($clone, 'rebalanceCallback');
        $reflectionProperty->setAccessible(true);

        self::assertSame($callback, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);

        $consumer = $clone
            ->withAdditionalBroker('localhost')
            ->withSubscription('test')
            ->withLogCallback($callback)
            ->build();
        $conf = $consumer->getConfiguration();
        self::assertArrayHasKey('rebalance_cb', $conf);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetOAuthBearerTokenRefreshCallback(): void
    {
        $callback = function () {
            // Anonymous test method, no logic required
        };

        $clone = $this->kafkaConsumerBuilder->withOAuthBearerTokenRefreshCallback($callback);

        $reflectionProperty = new \ReflectionProperty($clone, 'oauthBearerCallback');
        $reflectionProperty->setAccessible(true);

        self::assertSame($callback, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);

        $consumer = $clone
            ->withAdditionalBroker('localhost')
            ->withSubscription('test')
            ->withOAuthBearerTokenRefreshCallback($callback)
            ->build();
        $conf = $consumer->getConfiguration();
        self::assertArrayHasKey('oauthbearer_token_refresh_cb', $conf);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetOffsetCommitCallback(): void
    {
        $callback = function () {
            // Anonymous test method, no logic required
        };

        $clone = $this->kafkaConsumerBuilder->withOffsetCommitCallback($callback);

        $reflectionProperty = new \ReflectionProperty($clone, 'offsetCommitCallback');
        $reflectionProperty->setAccessible(true);

        self::assertSame($callback, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);

        $consumer = $clone
            ->withAdditionalBroker('localhost')
            ->withSubscription('test')
            ->withLogCallback($callback)
            ->build();
        $conf = $consumer->getConfiguration();
        self::assertArrayHasKey('offset_commit_cb', $conf);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetLogCallback(): void
    {
        $callback = function () {
            // Anonymous test method, no logic required
        };

        $clone = $this->kafkaConsumerBuilder->withLogCallback($callback);

        $reflectionProperty = new \ReflectionProperty($clone, 'logCallback');
        $reflectionProperty->setAccessible(true);

        self::assertSame($callback, $reflectionProperty->getValue($clone));
        self::assertNotSame($clone, $this->kafkaConsumerBuilder);
    }

    /**
     * @return void
     * @throws KafkaConsumerBuilderException
     */
    public function testBuildFailMissingBrokers(): void
    {
        self::expectException(KafkaConsumerBuilderException::class);
        self::expectExceptionMessage(KafkaConsumerBuilderException::NO_BROKER_EXCEPTION_MESSAGE);

        $this->kafkaConsumerBuilder->build();
    }

    /**
     * @return void
     */
    public function testBuildSuccess(): void
    {
        $callback = function ($kafka, $errId, $msg) {
            // Anonymous test method, no logic required
        };

        /** @var $consumer KafkaConsumer */
        $consumer = $this->kafkaConsumerBuilder
            ->withAdditionalBroker('localhost')
            ->withAdditionalSubscription('test-topic')
            ->withRebalanceCallback($callback)
            ->withOffsetCommitCallback($callback)
            ->withErrorCallback($callback)
            ->withLogCallback($callback)
            ->build();

        $conf = $consumer->getConfiguration();

        self::assertInstanceOf(KafkaConsumerInterface::class, $consumer);
        self::assertInstanceOf(KafkaConsumer::class, $consumer);
        self::assertArrayHasKey('enable.auto.commit', $conf);
        self::assertEquals($conf['enable.auto.commit'], 'false');
        self::assertArrayHasKey('rebalance_cb', $conf);
        self::assertArrayHasKey('offset_commit_cb', $conf);
        self::assertArrayHasKey('error_cb', $conf);
        self::assertArrayHasKey('log_cb', $conf);
    }
}
