<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Iterate;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\SpyReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
#[Group('csv')]
class ReaderComplexityTest extends TestCase
{
    private string $tempFile;
    private const ROW_COUNT = 200;

    public function setUp(): void
    {
        $this->tempFile = (string) tempnam(sys_get_temp_dir(), 'csv_complexity_');

        $handle = fopen($this->tempFile, 'w');
        assert(false !== $handle);

        fputcsv($handle, ['Id', 'Firstname', 'Value']);
        for ($i = 1; $i <= self::ROW_COUNT; ++$i) {
            fputcsv($handle, [(string) $i, "Name{$i}", "Value{$i}"]);
        }

        fclose($handle);
    }

    public function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testUnboundedRowsNeverSeeks(): void
    {
        $reader = new SpyReader($this->tempFile, hasHeaders: true);
        iterator_to_array($reader->rows());

        $this->assertSame(0, $reader->seekCount);
    }

    public function testUnboundedRowsNeverSeeksWithoutHeaders(): void
    {
        $reader = new SpyReader($this->tempFile, hasHeaders: false);
        iterator_to_array($reader->rows());

        $this->assertSame(0, $reader->seekCount);
    }

    public function testBoundedRowsSeeksOnce(): void
    {
        $reader = new SpyReader($this->tempFile, hasHeaders: true);
        iterator_to_array($reader->rows(1, 100));

        // O(N) invariant: exactly one seek() for initial positioning, never inside the loop
        $this->assertSame(1, $reader->seekCount);
    }

    public function testBoundedRowsSeeksOnceWithoutHeaders(): void
    {
        $reader = new SpyReader($this->tempFile, hasHeaders: false);
        iterator_to_array($reader->rows(0, 99));

        $this->assertSame(1, $reader->seekCount);
    }
}
