<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Model;

use Inwebo\Csv\Model\FiltersQueue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FiltersQueue::class)]
class FiltersQueueTest extends TestCase
{
    public function testFilter(): void
    {
        $queue = new FiltersQueue();
        $queue->push(fn (array $row) => true);
        $queue->rewind();
        $bool = $queue->filter([1, 2, 3]);
        $this->assertTrue($bool);

        $queue = new FiltersQueue();
        $queue->push(fn (array $row) => false);
        $queue->rewind();
        $bool = $queue->filter([1, 2, 3]);
        $this->assertFalse($bool);
    }

    public function testClear(): void
    {
        $queue = new FiltersQueue();
        $queue->push(fn (array $row) => true);
        $queue->push(fn (array $row) => false);
        $this->assertEquals(2, $queue->count());

        $queue->clear();
        $this->assertEquals(0, $queue->count());
    }
}
