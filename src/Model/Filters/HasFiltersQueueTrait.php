<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model\Filters;

trait HasFiltersQueueTrait
{
    /**
     * @var FiltersQueue<callable(array<int|string, ?string>):bool>
     */
    private FiltersQueue $filtersQueue;

    public function getFiltersQueue(): FiltersQueue
    {
        return $this->filtersQueue;
    }

    public function pushFilter(callable $callable): static
    {
        $this->filtersQueue->push($callable);

        return $this;
    }

    public function clearFilters(): static
    {
        $this->filtersQueue->clear();

        return $this;
    }

    /**
     * Applies all registered filter functions to the given line.
     * If any of the filter functions returns false, the line is considered invalid, and the method returns null.
     *
     * @param array<int|string, mixed> $row
     *
     * @return array<int|string, mixed>|null
     */
    public function filter(array $row): ?array
    {
        if (count($this->filtersQueue) > 0) {
            foreach ($this->filtersQueue as $filter) {
                if (false === $filter($row)) {
                    return null;
                }
            }
        }

        return $row;
    }
}
