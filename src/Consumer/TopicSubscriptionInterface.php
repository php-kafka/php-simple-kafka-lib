<?php

declare(strict_types=1);

namespace PhpKafka\Consumer;

interface TopicSubscriptionInterface
{

    /**
     * @return string
     */
    public function getTopicName(): string;

    /**
     * @return int[]
     */
    public function getPartitions(): array;

    /**
     * @param int[] $partitions
     * @return void
     */
    public function setPartitions(array $partitions): void;

    /**
     * @return integer
     */
    public function getOffset(): int;
}
