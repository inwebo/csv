<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Writer::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
class WriterTest extends TestCase
{
    private \SplTempFileObject $tempFile;
    private Writer $writer;

    public function setUp(): void
    {
        $this->tempFile = new \SplTempFileObject();
        $this->writer = new Writer($this->tempFile->getPathname(), 'w');
    }

    public function tearDown(): void
    {
        unset($this->writer, $this->tempFile);
    }

    public function testBOMDefaultFalse(): void
    {
        $this->assertFalse($this->writer->isBomEnabled());
    }

    public function testSetGetBOM(): void
    {
        $this->writer->setIsBomEnabled(true);
        $this->assertTrue($this->writer->isBomEnabled());

        $this->writer->setIsBomEnabled(false);
        $this->assertFalse($this->writer->isBomEnabled());
    }

    public function testLineEndingDefault(): void
    {
        $this->assertSame("\n", $this->writer->getLineEnding());
    }

    public function testSetGetLineEnding(): void
    {
        $this->writer->setLineEnding("\r\n");
        $this->assertSame("\r\n", $this->writer->getLineEnding());
    }

    public function testNormalizers(): void
    {
        $this->writer->pushNormalizer(function (array &$row): void {});
        $this->assertEquals(1, $this->writer->getNormalizersQueue()->count());

        $this->writer->clearNormalizers();
        $this->assertEquals(0, $this->writer->getNormalizersQueue()->count());
    }

    public function testFilters(): void
    {
        $this->writer->pushFilter(function (array $row): bool { return true; });
        $this->assertEquals(1, $this->writer->getFiltersQueue()->count());

        $this->writer->clearFilters();
        $this->assertEquals(0, $this->writer->getFiltersQueue()->count());
    }

    public function testQueuesAreProperInstances(): void
    {
        $this->assertInstanceOf(NormalizersQueue::class, $this->writer->getNormalizersQueue());
        $this->assertInstanceOf(FiltersQueue::class, $this->writer->getFiltersQueue());
    }

    public function testFluentInterface(): void
    {
        $result = $this->writer
            ->setIsBomEnabled(true)
            ->setLineEnding("\r\n")
            ->pushNormalizer(function (array &$row): void {})
            ->pushFilter(function (array $row): bool { return true; })
            ->setHeaders(['Id', 'Name'])
            ->row(['1', 'Alice'])
        ;

        $this->assertInstanceOf(Writer::class, $result);
    }
}
