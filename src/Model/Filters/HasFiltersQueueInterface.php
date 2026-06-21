<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model\Filters;

interface HasFiltersQueueInterface
{
    /**
     * @return FiltersQueue<callable(array<int|string, mixed>):bool>
     */
    public function getFiltersQueue(): FiltersQueue;

    /**
     * Adds a filter applied to each row before it is written.
     * If any filter returns false, the row is skipped. Filters are executed in FIFO order.
     *
     * @param callable(array<int|string, mixed>):bool $callable
     */
    public function pushFilter(callable $callable): static;

    /**
     * Removes all registered filters.
     */
    public function clearFilters(): static;
}
