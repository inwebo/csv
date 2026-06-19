<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Iterate;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
#[Group('csv')]
class ReaderReentrancyTest extends TestCase
{
    use FilesTrait;

    public function testRowsCalledTwiceWithHeaders(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        /** @var array<int, array<string, string>> $first */
        $first = iterator_to_array($reader->rows());
        /** @var array<int, array<string, string>> $second */
        $second = iterator_to_array($reader->rows());

        $this->assertCount(8, $first);
        $this->assertCount(8, $second);
        $this->assertEquals($first, $second);
        $this->assertEquals('Charles', $first[0]['Firstname']);
        $this->assertEquals('Emmanuel', $first[7]['Firstname']);
    }

    public function testRowsCalledTwiceWithoutHeaders(): void
    {
        $reader = new Reader($this->getWithoutHeaderFile(), hasHeaders: false);

        $first = iterator_to_array($reader->rows());
        $second = iterator_to_array($reader->rows());

        $this->assertCount(8, $first);
        $this->assertCount(8, $second);
        $this->assertEquals($first, $second);
        $this->assertEquals('Charles', $first[0][1]);
        $this->assertEquals('Emmanuel', $first[7][1]);
    }

    public function testBoundedRowsCalledTwiceWithHeaders(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        /** @var array<int, array<string, string>> $first */
        $first = iterator_to_array($reader->rows(1, 2));
        /** @var array<int, array<string, string>> $second */
        $second = iterator_to_array($reader->rows(1, 2));

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertEquals($first, $second);
        $this->assertEquals('Charles', $first[0]['Firstname']);
        $this->assertEquals('Georges', $first[1]['Firstname']);
    }

    public function testBoundedRowsCalledTwiceWithoutHeaders(): void
    {
        $reader = new Reader($this->getWithoutHeaderFile(), hasHeaders: false);

        $first = iterator_to_array($reader->rows(0, 1));
        $second = iterator_to_array($reader->rows(0, 1));

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertEquals($first, $second);
        $this->assertEquals('Charles', $first[0][1]);
        $this->assertEquals('Georges', $first[1][1]);
    }

    public function testFullRowsThenBoundedRows(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        /** @var array<int, array<string, string>> $full */
        $full = iterator_to_array($reader->rows());
        /** @var array<int, array<string, string>> $bounded */
        $bounded = iterator_to_array($reader->rows(1, 2));

        $this->assertCount(8, $full);
        $this->assertCount(2, $bounded);
        $this->assertEquals('Charles', $bounded[0]['Firstname']);
        $this->assertEquals('Georges', $bounded[1]['Firstname']);
    }

    public function testBoundedRowsThenFullRows(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);

        /** @var array<int, array<string, string>> $bounded */
        $bounded = iterator_to_array($reader->rows(1, 2));
        /** @var array<int, array<string, string>> $full */
        $full = iterator_to_array($reader->rows());

        $this->assertCount(2, $bounded);
        $this->assertCount(8, $full);
        $this->assertEquals('Charles', $full[0]['Firstname']);
        $this->assertEquals('Emmanuel', $full[7]['Firstname']);
    }
}
