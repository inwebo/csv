<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Iterate;

use Inwebo\Csv\Model\FiltersQueue;
use Inwebo\Csv\Model\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use Inwebo\Csv\Tests\Fixtures\Model\HasReaderTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[CoversClass(NormalizersQueue::class)]
#[CoversClass(FiltersQueue::class)]
#[Group('csv')]
#[Group('with-header')]
class ReadWithHeaderTest extends TestCase
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

    public function testColName(): void
    {
        $this->assertIsArray($this->getReader()->getHeaders());
        $this->assertEquals('Id', $this->getReader()->getHeaders()[0]);
        $this->assertEquals('Firstname', $this->getReader()->getHeaders()[1]);
        $this->assertEquals('Lastname', $this->getReader()->getHeaders()[2]);
        $this->assertEquals('Email', $this->getReader()->getHeaders()[3]);
    }

    public function testAt(): void
    {
        $headers = $this->getReader()->rowAt(0);
        $this->assertIsArray($headers);
        $this->assertEquals(1, $headers['Id']);
        $this->assertEquals('Charles', $headers['Firstname']);
        $this->assertEquals('de Gaulle', $headers['Lastname']);
        $this->assertEquals('', $headers['Email']);
    }

    public function testInvalidAt(): void
    {
        $row = $this->getReader()->rowAt(100);

        $this->assertFalse($row);
    }

    public function testCount(): void
    {
        $this->assertIsIterable($this->getReader()->rows());

        $rows = $this->getReader()->rows();

        $this->assertCount(8, iterator_to_array($rows));
    }

    public function testNormalizers(): void
    {
        $normalizer = function (array &$row) {
            $row['Id'] = (int) $row['Id'];
        };

        $this->getReader()->pushNormalizer($normalizer);

        $row = $this->getReader()->rowAt(0);
        $this->assertIsArray($row);
        $this->assertEquals(1, $row['Id']);
        $this->assertIsInt($row['Id']);
    }

    public function testFilters(): void
    {
        $normalizer = function (array &$row) {
            $row['Id'] = (int) $row['Id'];
        };

        $filter = function (array $row): bool {
            return $row['Id'] % 2 === 0;
        };

        $this->getReader()->pushNormalizer($normalizer);
        $this->getReader()->pushFilter($filter);

        $count = 0;

        foreach ($this->getReader()->rows() as $line) {
            $this->assertIsInt($line['Id']);
            $this->assertTrue($line['Id'] % 2 === 0);

            ++$count;
        }

        $this->assertEquals(4, $count);
    }
}
