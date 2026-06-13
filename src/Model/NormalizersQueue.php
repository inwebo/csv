<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model;

/**
 * @extends \SplQueue<callable>
 *
 * @phpstan-param $callable callable(array<int|string, mixed>):void
 *
 * @method void push(callable $callable)
 *
 * @phpstan-return callable(array<int|string, mixed>):void
 */
class NormalizersQueue extends \SplQueue implements ClearableInterface
{
    /**
     * @return callable(array<int|string, mixed>):void
     */
    public function current(): callable
    {
        /* @var callable */
        return parent::current();
    }

    /**
     * @param array<int|string, mixed> $row
     */
    public function normalize(array &$row): void
    {
        call_user_func_array($this->current(), [&$row]);
    }

    public function clear(): void
    {
        while (!$this->isEmpty()) {
            $this->dequeue();
        }
    }
}
