<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Instance;

use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
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

    public function testEmpty(): void
    {
        $reader = (new Reader($this->getEmptyFile(), hasHeaders: false));
        $this->assertInstanceOf(Reader::class, $reader);

        $rows = iterator_to_array($reader->rows());

        $this->assertCount(0, $rows);
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
