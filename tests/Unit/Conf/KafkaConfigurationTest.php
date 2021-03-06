<?php

namespace PhpKafka\Tests\Unit\Kafka\Conf;

use PhpKafka\Consumer\KafkaConsumerBuilder;
use PhpKafka\Consumer\TopicSubscription;
use PhpKafka\Configuration\KafkaConfiguration;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \PhpKafka\Configuration\KafkaConfiguration
 */
class KafkaConfigurationTest extends TestCase
{

    /**
     * @return void
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(KafkaConfiguration::class, new KafkaConfiguration([], []));
    }

    /**
     * @return array
     */
    public function kafkaConfigurationDataProvider(): array
    {
        $brokers = ['localhost'];
        $topicSubscriptions = [new TopicSubscription('test-topic')];

        return [
            [
                $brokers,
                $topicSubscriptions
            ]
        ];
    }

    /**
     * @dataProvider kafkaConfigurationDataProvider
     * @param array $brokers
     * @param array $topicSubscriptions
     * @return void
     */
    public function testGettersAndSetters(array $brokers, array $topicSubscriptions): void
    {
        $kafkaConfiguration = new KafkaConfiguration($brokers, $topicSubscriptions);

        self::assertEquals($brokers, $kafkaConfiguration->getBrokers());
        self::assertEquals($topicSubscriptions, $kafkaConfiguration->getTopicSubscriptions());
    }

    /**
     * @dataProvider kafkaConfigurationDataProvider
     * @param array $brokers
     * @param array $topicSubscriptions
     * @return void
     */
    public function testGetConfiguration(array $brokers, array $topicSubscriptions): void
    {
        $kafkaConfiguration = new KafkaConfiguration($brokers, $topicSubscriptions);

        self::assertEquals($kafkaConfiguration->dump(), $kafkaConfiguration->getConfiguration());
    }

    /**
     * @return array
     */
    public function configValuesProvider(): array
    {
        return [
            [ 1, '1' ],
            [ -1, '-1' ],
            [ 1.123333, '1.123333' ],
            [ -0.99999, '-0.99999' ],
            [ true, 'true' ],
            [ false, 'false' ],
            [ null, '' ],
            [ '', '' ],
            [ '  ', '  ' ],
            [ [], null ],
            [ new stdClass(), null ],
        ];
    }

    /**
     * @dataProvider configValuesProvider
     * @param mixed $inputValue
     * @param mixed $expectedValue
     */
    public function testConfigValues($inputValue, $expectedValue): void
    {
        $kafkaConfiguration = new KafkaConfiguration(
            ['localhost'],
            [new TopicSubscription('test-topic')],
            [
                'group.id' => $inputValue,
                'auto.commit.interval.ms' => 100
            ]
        );

        $config = $kafkaConfiguration->getConfiguration();

        if (null === $expectedValue) {
            self::assertArrayNotHasKey('group.id', $config);
            return;
        }

        self::assertEquals($config['metadata.broker.list'], 'localhost');
        self::assertEquals($expectedValue, $config['group.id']);
        self::assertEquals('100', $config['auto.commit.interval.ms']);
    }
}
