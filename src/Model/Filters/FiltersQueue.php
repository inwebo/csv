<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model\Filters;

use Inwebo\Csv\Model\ClearableInterface;

/**
 * @extends \SplQueue<callable>
 *
 * @phpstan-param $callable callable(array<int|string, mixed>):bool
 *
 * @method void push(callable $callable)
 *
 * @phpstan-return callable(array<int|string, mixed>):bool
 */
class FiltersQueue extends \SplQueue implements ClearableInterface
{
    /**
     * @return callable(array<int|string, mixed>):bool
     */
    public function current(): callable
    {
        /* @var callable */
        return parent::current();
    }

    /**
     * @param array<int|string, mixed> $row
     */
    public function filter(array $row): bool
    {
        return call_user_func($this->current(), $row);
    }

    public function clear(): void
    {
        while (!$this->isEmpty()) {
            $this->dequeue();
        }
    }
}
