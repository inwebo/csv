<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Model;

use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NormalizersQueue::class)]
class NormalizersQueueTest extends TestCase
{
    public function testNormalize(): void
    {
        $queue = new NormalizersQueue();
        $queue->push(fn (array $row) => $row);
        $queue->rewind();
        $row = [1, 2, 3];
        $queue->normalize($row);
        $this->assertIsArray($row);
    }

    public function testClear(): void
    {
        $queue = new NormalizersQueue();
        $queue->push(fn (array &$row) => null);
        $queue->push(fn (array &$row) => null);
        $this->assertEquals(2, $queue->count());

        $queue->clear();
        $this->assertEquals(0, $queue->count());
    }
}
