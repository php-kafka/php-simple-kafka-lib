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

## Examples
Examples can be found [here](https://github.com/php-kafka/php-kafka-examples/tree/main/src/ext-php-simple-kafka-client/php-simple-kafka-lib)
