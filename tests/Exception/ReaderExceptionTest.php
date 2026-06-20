<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Exception;

use Inwebo\Csv\Exception\InvalidRangeException;
use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[UsesClass(InvalidRangeException::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
class ReaderExceptionTest extends TestCase
{
    use FilesTrait;

    public function testExceptionLinesToIsNull(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(null, 12);
        $this->expectException(InvalidRangeException::class);
        $this->expectExceptionMessage('The $to parameter must be null when $from is null');
        $rows->current();
    }

    public function testExceptionLinesFromIsNull(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(1, null);
        $this->expectException(InvalidRangeException::class);
        $this->expectExceptionMessage('The $from parameter must be null when $to is null');
        $rows->current();
    }

    public function testExceptionLinesFromIsGreaterThanTo(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(10, 5);
        $this->expectException(InvalidRangeException::class);
        $this->expectExceptionMessage('The $from parameter must be less than or equal to $to');
        $rows->current();
    }

    public function testExceptionFromZeroWithHeaders(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(0, 2);
        $this->expectException(InvalidRangeException::class);
        $this->expectExceptionMessage('The $from parameter must be >= 1');
        $rows->current();
    }

    public function testExceptionFromNegativeWithoutHeaders(): void
    {
        $reader = new Reader($this->getWithoutHeaderFile(), hasHeaders: false);

        $rows = $reader->rows(-1, 2);
        $this->expectException(InvalidRangeException::class);
        $this->expectExceptionMessage('The $from parameter must be >= 0');
        $rows->current();
    }

    public function testSetFlagsThrowsWhenAllFlagsMissing(): void
    {
        $reader = new Reader($this->getWithHeaderFile());
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Reader requires flags READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD.');
        $reader->setFlags(0);
    }

    public function testSetFlagsThrowsWhenReadCsvMissing(): void
    {
        $reader = new Reader($this->getWithHeaderFile());
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Reader requires flags READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD.');
        $reader->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD);
    }

    public function testSetFlagsAllowsRequiredFlags(): void
    {
        $reader = new Reader($this->getWithHeaderFile());
        $reader->setFlags(
            \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD
        );
        $this->assertInstanceOf(Reader::class, $reader);
    }
}
