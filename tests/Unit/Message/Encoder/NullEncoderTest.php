<?php

declare(strict_types=1);

namespace PhpKafka\Tests\Unit\Kafka\Message\Encoder;

use PhpKafka\Message\KafkaProducerMessageInterface;
use PhpKafka\Message\Encoder\NullEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpKafka\Message\Encoder\NullEncoder
 */
class NullEncoderTest extends TestCase
{

    /**
     * @return void
     */
    public function testEncode(): void
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessageInterface::class);

        $this->assertSame($message, (new NullEncoder())->encode($message));
    }
}
