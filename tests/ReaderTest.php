<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests;

use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
class ReaderTest extends TestCase
{
    use FilesTrait;

    private Reader $reader;

    public function setUp(): void
    {
        $this->reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);
    }

    public function testNormalizers(): void
    {
        $this->reader->pushNormalizer(function (array &$row) {});
        $this->assertEquals(1, $this->reader->getNormalizersQueue()->count());

        $this->reader->clearNormalizers();
        $this->assertEquals(0, $this->reader->getNormalizersQueue()->count());
    }

    public function testFilters(): void
    {
        $this->reader->pushFilter(function (array $row) { return true; });
        $this->assertEquals(1, $this->reader->getFiltersQueue()->count());

        $this->reader->clearFilters();
        $this->assertEquals(0, $this->reader->getFiltersQueue()->count());
    }

    public function testHeader(): void
    {
        $this->assertTrue($this->reader->hasHeaders());
        $this->assertIsArray($this->reader->getHeaders());
    }
}
