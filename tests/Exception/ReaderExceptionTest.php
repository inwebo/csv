<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Exception;

use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
class ReaderExceptionTest extends TestCase
{
    use FilesTrait;

    public function testExceptionLinesToIsNull(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(null, 12);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $to parameter must be null when $from is null');
        $rows = $rows->current();
    }

    public function testExceptionLinesFromIsNull(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(1, null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $from parameter must be null when $to is null');
        $rows = $rows->current();
    }

    public function testExceptionLinesFromIsGreaterThanTo(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(10, 5);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $from parameter must be less than or equal to $to');
        $rows = $rows->current();
    }

    public function testExceptionFromZeroWithHeaders(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        $rows = $reader->rows(0, 2);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $from parameter must be >= 1');
        $rows->current();
    }

    public function testExceptionFromNegativeWithoutHeaders(): void
    {
        $reader = new Reader($this->getWithoutHeaderFile(), hasHeaders: false);

        $rows = $reader->rows(-1, 2);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $from parameter must be >= 0');
        $rows->current();
    }
}
