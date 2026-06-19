<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model\Normalizers;

interface HasNormalizersQueueInterface
{
    /**
     * @return NormalizersQueue<callable(array<int|string, ?string> &$row):void>
     */
    public function getNormalizersQueue(): NormalizersQueue;

    /**
     * Adds a normalizer applied to each row before it is written.
     * Normalizers receive the row by reference and are executed in FIFO order.
     *
     * @param callable(array<int|string, ?string> &$row):void $callable
     */
    public function pushNormalizer(callable $callable): static;

    /**
     * Removes all registered normalizers.
     */
    public function clearNormalizers(): static;
}
