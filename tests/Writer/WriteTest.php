<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Writer;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use Inwebo\Csv\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Writer::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
#[UsesClass(Reader::class)]
#[Group('csv')]
#[Group('write')]
class WriteTest extends TestCase
{
    use FilesTrait;

    private string $tempFile;
    private Writer $writer;

    public function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csv_writer_test_');
        $this->writer = new Writer($this->tempFile);
    }

    public function tearDown(): void
    {
        unset($this->writer);
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testWriteRow(): void
    {
        $this->writer->row(['1', 'Alice', 'alice@example.com']);

        $reader = new Reader($this->tempFile, hasHeaders: false);
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(1, $rows);
        $this->assertSame(['1', 'Alice', 'alice@example.com'], $rows[0]);
    }

    public function testWriteRowsFromArray(): void
    {
        $data = [
            ['1', 'Alice'],
            ['2', 'Bob'],
            ['3', 'Charlie'],
        ];

        $this->writer->rows($data);

        $reader = new Reader($this->tempFile, hasHeaders: false);
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(3, $rows);
        $this->assertSame(['2', 'Bob'], $rows[1]);
    }

    public function testWriteRowsFromGenerator(): void
    {
        $generator = (function () {
            yield ['1', 'Alice'];
            yield ['2', 'Bob'];
        })();

        $this->writer->rows($generator);

        $reader = new Reader($this->tempFile, hasHeaders: false);
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(2, $rows);
    }

    public function testSetHeader(): void
    {
        $this->writer
            ->setHeaders(['Id', 'Name', 'Email'])
            ->row(['1', 'Alice', 'alice@example.com'])
        ;

        $reader = new Reader($this->tempFile);
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('Id', $rows[0]);
        $this->assertArrayHasKey('Name', $rows[0]);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    public function testBOMIsWrittenFirst(): void
    {
        $writer = new Writer($this->tempFile, isBomEnabled: true);
        $writer->row(['Id', 'Name']);
        unset($writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function testNoBOMByDefault(): void
    {
        $this->writer->row(['Id', 'Name']);
        unset($this->writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $content);
    }

    public function testWindowsLineEnding(): void
    {
        $this->writer->setLineEnding("\r\n");
        $this->writer->row(['Id', 'Name']);
        unset($this->writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString("\r\n", $content);
    }

    public function testCustomDelimiterViaCsvControl(): void
    {
        $this->writer->setCsvControl(';');
        $this->writer->row(['1', 'Alice']);
        unset($this->writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString(';', $content);
    }

    public function testBOMNotWrittenInAppendModeOnNonEmptyFile(): void
    {
        // Write initial content without BOM
        $this->writer->row(['Id', 'Name']);
        unset($this->writer);

        // Re-open in append mode with BOM enabled
        $writer = new Writer($this->tempFile, mode: 'a', isBomEnabled: true);
        $writer->row(['1', 'Alice']);
        unset($writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $content);
    }

    public function testFilterSkipsRow(): void
    {
        $this->writer->pushFilter(fn (array $row): bool => '2' !== $row[0]);
        $this->writer->rows([['1', 'Alice'], ['2', 'Bob'], ['3', 'Charlie']]);
        unset($this->writer);

        $reader = new Reader($this->tempFile, hasHeaders: false);
        $rows = iterator_to_array($reader->rows());

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0][1]);
        $this->assertSame('Charlie', $rows[1][1]);
    }

    public function testNormalizerTransformsRow(): void
    {
        $this->writer->pushNormalizer(function (array &$row): void {
            $row[1] = strtoupper($row[1]);
        });
        $this->writer->row(['1', 'alice']);
        unset($this->writer);

        $reader = new Reader($this->tempFile, hasHeaders: false);
        $rows = iterator_to_array($reader->rows());

        $this->assertSame('ALICE', $rows[0][1]);
    }

    public function testFilterDoesNotApplyToHeader(): void
    {
        $this->writer->pushFilter(fn (array $row): bool => false);
        $this->writer->setHeaders(['Id', 'Name']);
        $this->writer->row(['1', 'Alice']);
        unset($this->writer);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('Id,Name', $content);
    }

    public function testETLReaderToWriter(): void
    {
        $reader = new Reader($this->getWithHeaderFile());

        $this->writer->setHeaders($reader->getHeaders());
        $this->writer->rows($reader->rows());
        unset($this->writer);

        $result = new Reader($this->tempFile);
        $rows = iterator_to_array($result->rows());

        $this->assertCount(8, $rows);
        $this->assertSame('Charles', $rows[0]['Firstname']);
        $this->assertSame('Macron', $rows[7]['Lastname']);
    }
}
