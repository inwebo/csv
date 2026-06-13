<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Filtered;

use Inwebo\Csv\Model\FiltersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[CoversClass(FiltersQueue::class)]
class FilteredTest extends TestCase
{
    use FilesTrait;

    public function testFilteredGenderM(): void
    {
        $reader = new Reader($this->getFilteredFile());

        $reader->pushFilter(function (
            /*
             * @var array{
             *     Name: string,
             *     Age: int,
             *     City: string,
             *     Salary: string,
             *     Gender: string,
             * } $line
             */
            $row,
        ): bool {
            return 'M' === $row['Gender'];
        });

        /** @var \Generator<array<string, string>> $generator */
        $generator = $reader->rows();
        foreach ($generator as $line) {
            $this->assertEquals('M', $line['Gender']);
        }
    }
}
