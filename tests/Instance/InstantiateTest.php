<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Instance;

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
#[Group('instantiate')]
class InstantiateTest extends TestCase
{
    use FilesTrait;

    public function testInvalidInstantiate(): void
    {
        $this->expectException(\RuntimeException::class);
        new Reader('unknown-file.csv');
    }

    public function testValidWithHeaderInstantiate(): void
    {
        $reader = new Reader($this->getWithHeaderFile());
        $this->assertInstanceOf(Reader::class, $reader);
    }

    public function testValidWithoutHeaderInstantiate(): void
    {
        $iterator = (new Reader($this->getWithoutHeaderFile(), hasHeaders: false));
        $this->assertInstanceOf(Reader::class, $iterator);
    }

    public function testEmptyWithoutHeaders(): void
    {
        $reader = new Reader($this->getEmptyFile(), hasHeaders: false);
        $this->assertInstanceOf(Reader::class, $reader);
        $this->assertCount(0, iterator_to_array($reader->rows()));
    }

    public function testEmptyWithHeaders(): void
    {
        $reader = new Reader($this->getEmptyFile(), hasHeaders: true);
        $this->assertInstanceOf(Reader::class, $reader);
        $this->assertEmpty($reader->getHeaders());
        $this->assertCount(0, iterator_to_array($reader->rows()));
        $this->assertFalse($reader->rowAt());
        $this->assertFalse($reader->rowAt(0));
    }

    public function testMalformedCsvRow(): void
    {
        $reader = new Reader($this->getMalformedFile());
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(2, $rows);
        $this->assertEquals('1', $rows[0]['Id']);
        $this->assertEquals('Charles', $rows[0]['Firstname']);
        $this->assertNull($rows[0]['Lastname']);
        $this->assertNull($rows[0]['Email']);
        $this->assertEquals('Georges', $rows[1]['Firstname']);
        $this->assertEquals('Pompidou', $rows[1]['Lastname']);
    }
}
