# php-simple-kafka-lib

[![CircleCI](https://circleci.com/gh/php-kafka/php-simple-kafka-lib.svg?style=shield)](https://circleci.com/gh/php-kafka/php-simple-kafka-lib)
[![Maintainability](https://api.codeclimate.com/v1/badges/1ca1ebf269b4d40bb52c/maintainability)](https://codeclimate.com/github/php-kafka/php-simple-kafka-lib/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1ca1ebf269b4d40bb52c/test_coverage)](https://codeclimate.com/github/php-kafka/php-simple-kafka-lib/test_coverage) 
[![Latest Stable Version](https://poser.pugx.org/php-kafka/php-simple-kafka-lib/v/stable)](https://packagist.org/packages/php-kafka/php-simple-kafka-lib) 
[![Latest Unstable Version](https://poser.pugx.org/php-kafka/php-simple-kafka-lib/v/unstable)](https://packagist.org/packages/php-kafka/php-simple-kafka-lib)  
[![Join the chat at https://gitter.im/php-kafka/php-simple-kafka-lib](https://badges.gitter.im/php-kafka/php-simple-kafka-lib.svg)](https://gitter.im/php-kafka/php-simple-kafka-lib?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## Description
This is a library that makes it easier to use Kafka in your PHP project.  

This library relies on [php-kafka/php-simple-kafka-client](https://github.com/php-kafka/php-simple-kafka-client)  
Avro support relies on [flix-tech/avro-serde-php](https://github.com/flix-tech/avro-serde-php)  
The [documentation](https://php-kafka.github.io/php-simple-kafka-client.github.io/) of the php extension,  
can help out to understand the internals of this library.

## Requirements
- php: ^7.3|^8.0
- ext-simple_kafka_client: >=0.1.0
- librdkafka: >=1.4.0

## Installation
```
composer require php-kafka/php-simple-kafka-lib
```

### Enable Avro support
If you need Avro support, run:
```
composer require flix-tech/avro-serde-php "~1.4"
```

## Credits
This library was inspired by [jobcloud/php-kafka-lib](https://github.com/jobcloud/php-kafka-lib) :heart_eyes:

## Usage

### Simple Producer
```php
<?php

use PhpKafka\Message\KafkaProducerMessage;
use PhpKafka\Producer\KafkaProducerBuilder;

$producer = KafkaProducerBuilder::create()
    ->withAdditionalBroker('localhost:9092')
    ->build();

$message = KafkaProducerMessage::create('test-topic', 0)
            ->withKey('asdf-asdf-asfd-asdf')
            ->withBody('some test message payload')
            ->withHeaders([ 'key' => 'value' ]);

$producer->produce($message);

// Shutdown producer, flush messages that are in queue. Give up after 20s
$result = $producer->flush(20000);
```

### Transactional producer
```php
<?php

use PhpKafka\Message\KafkaProducerMessage;
use PhpKafka\Producer\KafkaProducerBuilder;
use PhpKafka\Exception\KafkaProducerTransactionRetryException;
use PhpKafka\Exception\KafkaProducerTransactionAbortException;
use PhpKafka\Exception\KafkaProducerTransactionFatalException;

$producer = KafkaProducerBuilder::create()
    ->withAdditionalBroker('localhost:9092')
    ->build();

$message = KafkaProducerMessage::create('test-topic', 0)
            ->withKey('asdf-asdf-asfd-asdf')
            ->withBody('some test message payload')
            ->withHeaders([ 'key' => 'value' ]);
try {
    $producer->beginTransaction(10000);
    $producer->produce($message);
    $producer->commitTransaction(10000);
} catch (KafkaProducerTransactionRetryException $e) {
    // something went wrong but you can retry the failed call (either beginTransaction or commitTransaction)
} catch (KafkaProducerTransactionAbortException $e) {
    // you need to call $producer->abortTransaction(10000); and try again
} catch (KafkaProducerTransactionFatalException $e) {
    // something went very wrong, re-create your producer, otherwise you could jeopardize the idempotency guarantees
}

// Shutdown producer, flush messages that are in queue. Give up after 20s
$result = $producer->flush(20000);
```

### Avro Producer
To create an avro prodcuer add the avro encoder.  

```php
<?php

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use PhpKafka\Message\KafkaProducerMessage;
use PhpKafka\Message\Encoder\AvroEncoder;
use PhpKafka\Message\Registry\AvroSchemaRegistry;
use PhpKafka\Producer\KafkaProducerBuilder;
use PhpKafka\Message\KafkaAvroSchema;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use GuzzleHttp\Client;

$cachedRegistry = new CachedRegistry(
    new BlockingRegistry(
        new PromisingRegistry(
            new Client(['base_uri' => 'kafka-schema-registry:9081'])
        )
    ),
    new AvroObjectCacheAdapter()
);

$registry = new AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('bodySchemaName' /*, int $version, AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('keySchemaName' /*, int $version, AvroSchema $definition */)
);

// if you are only encoding key or value, you can pass that mode as additional third argument
// per default both key and body will get encoded
$encoder = new AvroEncoder($registry, $recordSerializer /*, AvroEncoderInterface::ENCODE_BODY */);

$producer = KafkaProducerBuilder::create()
    ->withAdditionalBroker('kafka:9092')
    ->withEncoder($encoder)
    ->build();

$schemaName = 'testSchema';
$version = 1;
$message = KafkaProducerMessage::create('test-topic', 0)
            ->withKey('asdf-asdf-asfd-asdf')
            ->withBody(['name' => 'someName'])
            ->withHeaders([ 'key' => 'value' ]);

$producer->produce($message);

// Shutdown producer, flush messages that are in queue. Give up after 20s
$result = $producer->flush(20000);
```

**NOTE:** To improve producer latency you can install the `pcntl` extension.  
The php-simple-kafka-lib already has code in place, similarly described here:  
https://github.com/arnaud-lb/php-rdkafka#performance--low-latency-settings

### Simple Consumer

```php
<?php

use PhpKafka\Consumer\KafkaConsumerBuilder;
use PhpKafka\Exception\KafkaConsumerConsumeException;
use PhpKafka\Exception\KafkaConsumerEndOfPartitionException;
use PhpKafka\Exception\KafkaConsumerTimeoutException;

$consumer = KafkaConsumerBuilder::create()
     ->withAdditionalConfig(
        [
            'compression.codec' => 'lz4',
            'auto.commit.interval.ms' => 500
        ]
    )
    ->withAdditionalBroker('kafka:9092')
    ->withConsumerGroup('testGroup')
    ->withAdditionalSubscription('test-topic')
    ->build();

$consumer->subscribe();

while (true) {
    try {
        $message = $consumer->consume();
        // your business logic
        $consumer->commit($message);
    } catch (KafkaConsumerTimeoutException $e) {
        //no messages were read in a given time
    } catch (KafkaConsumerEndOfPartitionException $e) {
        //only occurs if enable.partition.eof is true (default: false)
    } catch (KafkaConsumerConsumeException $e) {
        // Failed
    }
}
```

### Avro Consumer
To create an avro consumer add the avro decoder.  

```php
<?php

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use PhpKafka\Messaging\Kafka\Consumer\KafkaConsumerBuilder;
use PhpKafka\Exception\KafkaConsumerConsumeException;
use PhpKafka\Exception\KafkaConsumerEndOfPartitionException;
use PhpKafka\Exception\KafkaConsumerTimeoutException;
use PhpKafka\Message\Decoder\AvroDecoder;
use PhpKafka\Message\KafkaAvroSchema;
use PhpKafka\Message\Registry\AvroSchemaRegistry;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use GuzzleHttp\Client;

$cachedRegistry = new CachedRegistry(
    new BlockingRegistry(
        new PromisingRegistry(
            new Client(['base_uri' => 'kafka-schema-registry:9081'])
        )
    ),
    new AvroObjectCacheAdapter()
);

$registry = new AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('bodySchema' , 9 /* , AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('keySchema' , 9 /* , AvroSchema $definition */)
);

// if you are only decoding key or value, you can pass that mode as additional third argument
// per default both key and body will get decoded
$decoder = new AvroDecoder($registry, $recordSerializer /*, AvroDecoderInterface::DECODE_BODY */);

$consumer = KafkaConsumerBuilder::create()
     ->withAdditionalConfig(
        [
            'compression.codec' => 'lz4',
            'auto.commit.interval.ms' => 500
        ]
    )
    ->withDecoder($decoder)
    ->withAdditionalBroker('kafka:9092')
    ->withConsumerGroup('testGroup')
    ->withAdditionalSubscription('test-topic')
    ->build();

$consumer->subscribe();

while (true) {
    try {
        $message = $consumer->consume();
        // your business logic
        $consumer->commit($message);
    } catch (KafkaConsumerTimeoutException $e) {
        //no messages were read in a given time
    } catch (KafkaConsumerEndOfPartitionException $e) {
        //only occurs if enable.partition.eof is true (default: false)
    } catch (KafkaConsumerConsumeException $e) {
        // Failed
    } 
}
```

