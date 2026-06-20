<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Example;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[CoversClass(FiltersQueue::class)]
#[CoversClass(NormalizersQueue::class)]
class ExampleTest extends TestCase
{
    use FilesTrait;

    public function testExample(): void
    {
        $reader = new Reader($this->getExampleFile(), hasHeaders: true);

        $reader->pushNormalizer(function (array &$row) {
            if (empty($row['Gender'])) {
                $row['Gender'] = 'U';
            }

            $gender = strtolower($row['Gender']);

            if (str_starts_with($gender, 'm')) {
                $row['Gender'] = 'M';
            }

            if (str_starts_with($gender, 'f')) {
                $row['Gender'] = 'F';
            }
        })

        ->pushNormalizer(function (array &$row) {
            if (is_null($row['Salary'])) {
                $row['Salary'] = 0;
            }

            if (is_string($row['Salary'])) {
                $row['Salary'] = (int) $row['Salary'];
            }
        })
        ->pushNormalizer(function (array &$row) {
            if (is_null($row['City'])) {
                $row['City'] = 'Toulouse';
            }
        })
        ->pushNormalizer(function (array &$row) {
            if (is_null($row['Age'])) {
                $row['Age'] = -1;
            } else {
                $row['Age'] = (int) $row['Age'];
            }
        })
        ->pushNormalizer(function (array &$row) {
            if (is_string($row['FirstName'])) {
                $row['FirstName'] = trim($row['FirstName']);
            } else {
                $row['FirstName'] = 'Unknown';
            }

            if (is_string($row['LastName'])) {
                $row['LastName'] = trim($row['LastName']);
            } else {
                $row['LastName'] = 'Unknown';
            }
        })
        ;

        $rows = $reader->rows();
        while ($rows->valid()) {
            $current = $rows->current();

            $this->assertNotNull($current['City']);
            $this->assertNotNull($current['Salary']);
            $this->assertNotNull($current['Age']);
            $this->assertIsInt($current['Salary']);
            $this->assertIsInt($current['Age']);
            $this->assertNotNull($current['Gender']);
            $this->assertContains($current['Gender'], ['M', 'F', 'U']);

            $rows->next();
        }

        $reader->pushFilter(function (array $row): bool {
            return $row['Salary'] > 80000 && $row['Age'] > 25 && 'M' === $row['Gender'];
        });

        /**
         * @var \Generator<array{FirstName: string, LastName: string, Age: int, City: string, Salary: int, Gender: string, Email: string, Phone: string}> $rows
         */
        $rows = $reader->rows();
        while ($rows->valid()) {
            $current = $rows->current();

            $this->assertGreaterThan(80000, $current['Salary']);
            $this->assertGreaterThan(25, $current['Age']);
            $this->assertEquals('M', $current['Gender']);

            $rows->next();
        }

        $reader->clearFilters();

        $reader->pushFilter(function (array $row): bool {
            return $row['Salary'] > 90000
                && $row['Age'] >= 19
                && 'F' === $row['Gender']
                && 'Unknown' !== $row['FirstName']
                && 'Unknown' !== $row['LastName']
            ;
        });

        $rows = $reader->rows();
        while ($rows->valid()) {
            $current = $rows->current();

            $this->assertGreaterThan(90000, $current['Salary']);
            $this->assertGreaterThanOrEqual(19, $current['Age']);
            $this->assertEquals('F', $current['Gender']);
            $this->assertNotEquals('Unknown', $current['FirstName']);
            $this->assertNotEquals('Unknown', $current['LastName']);

            $rows->next();
        }
    }
}
