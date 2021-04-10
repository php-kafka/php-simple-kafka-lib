<?php

declare(strict_types=1);

namespace PhpKafka\Message\Encoder;

use PhpKafka\Message\Registry\AvroSchemaRegistryInterface;

interface AvroEncoderInterface extends EncoderInterface
{
    /**
     * @return AvroSchemaRegistryInterface
     */
    public function getRegistry(): AvroSchemaRegistryInterface;
}
