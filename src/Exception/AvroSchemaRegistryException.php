<?php

declare(strict_types=1);

namespace PhpKafka\Exception;

use RuntimeException;

class AvroSchemaRegistryException extends RuntimeException
{
    public const SCHEMA_MAPPING_NOT_FOUND = 'There was no schema mapping for topic: %s, type: %s';
}
