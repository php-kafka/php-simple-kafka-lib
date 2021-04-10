<?php

declare(strict_types=1);

namespace PhpKafka\Tests\Unit\Kafka\Message\Decoder;

use PhpKafka\Message\Decoder\JsonDecoder;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Message\Decoder\JsonDecoder
 */
class JsonDecoderTest extends TestCase
{

    /**
     * @return void
     */
    public function testDecode(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message->expects(self::once())->method('getBody')->willReturn('{"name":"foo"}');
        $decoder = new JsonDecoder();
        $result = $decoder->decode($message);

        self::assertInstanceOf(KafkaConsumerMessageInterface::class, $result);
        self::assertEquals(['name' => 'foo'], $result->getBody());
    }

    /**
     * @return void
     */
    public function testDecodeNonJson(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);
        $message->expects(self::once())->method('getBody')->willReturn('test');
        $decoder = new JsonDecoder();

        self::expectException(\JsonException::class);

        $decoder->decode($message);
    }
}
