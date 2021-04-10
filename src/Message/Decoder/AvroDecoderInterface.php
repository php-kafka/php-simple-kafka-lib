<?php

declare(strict_types=1);

namespace PhpKafka\Message\Decoder;

use PhpKafka\Message\Registry\AvroSchemaRegistryInterface;

interface AvroDecoderInterface extends DecoderInterface
{
    /**
     * @return AvroSchemaRegistryInterface
     */
    public function getRegistry(): AvroSchemaRegistryInterface;
}
