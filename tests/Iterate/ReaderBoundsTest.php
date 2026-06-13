<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Iterate;

use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use Inwebo\Csv\Tests\Fixtures\Model\HasReaderTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[Group('csv')]
#[Group('with-header')]
class ReaderBoundsTest extends TestCase
{
    use FilesTrait;
    use HasReaderTrait;
    private ?Reader $reader;

    public function setUp(): void
    {
        $this->reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);
        $this->assertTrue($this->getReader()->hasHeaders());
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testBoundsWWithHeader(): void
    {
        /**
         * @var array<int, array<string, string>> $rows
         */
        $rows = iterator_to_array($this->getReader()->rows(1, 2));
        $this->assertEquals('Charles', $rows[0]['Firstname']);
        $this->assertEquals('Georges', $rows[1]['Firstname']);
    }

    public function testBoundsWWithoutHeader(): void
    {
        $reader = new Reader($this->getWithHeaderFile(), hasHeaders: true);
        /**
         * @var array<int, array<string, string>> $rows
         */
        $rows = iterator_to_array($reader->rows(1, 2));
        $this->assertEquals('Charles', $rows[0]['Firstname']);
        $this->assertEquals('Georges', $rows[1]['Firstname']);
    }
}
