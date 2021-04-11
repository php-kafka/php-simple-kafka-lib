<?php

declare(strict_types=1);

namespace PhpKafka\Tests\Unit\Kafka\Message\Decoder;

use PhpKafka\Message\Decoder\NullDecoder;
use PhpKafka\Message\KafkaConsumerMessageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Message\Decoder\NullDecoder
 */
class NullDecoderTest extends TestCase
{

    /**
     * @return void
     */
    public function testDecode(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessageInterface::class);

        self::assertSame($message, (new NullDecoder())->decode($message));
    }
}
