<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Iterate;

use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Reader;
use Inwebo\Csv\Tests\Fixtures\Model\FilesTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
#[Group('csv')]
class ReaderBoundsTest extends TestCase
{
    use FilesTrait;

    /**
     * @return array<string, array{bool, ?int, ?int, int, string, string}>
     */
    public static function boundsProvider(): array
    {
        return [
            // hasHeaders: true — firstname at key 'Firstname'
            'with-headers, full' => [true,  null, null, 8, 'Charles',   'Emmanuel'],
            'with-headers, row 1' => [true,  1,    1,    1, 'Charles',   'Charles'],
            'with-headers, rows 1-2' => [true,  1,    2,    2, 'Charles',   'Georges'],
            'with-headers, rows 1-8' => [true,  1,    8,    8, 'Charles',   'Emmanuel'],
            'with-headers, rows 3-5' => [true,  3,    5,    3, 'Valéry',    'Jacques'],
            // hasHeaders: false — firstname at column index 1, 'Valéry ' has a trailing space in the fixture
            'without-headers, full' => [false, null, null, 8, 'Charles',   'Emmanuel'],
            'without-headers, row 0' => [false, 0,    0,    1, 'Charles',   'Charles'],
            'without-headers, rows 0-2' => [false, 0,    2,    3, 'Charles',   'Valéry '],
            'without-headers, rows 0-7' => [false, 0,    7,    8, 'Charles',   'Emmanuel'],
            'without-headers, rows 3-5' => [false, 3,    5,    3, 'François',  'Nicolas'],
        ];
    }

    #[DataProvider('boundsProvider')]
    public function testBounds(
        bool $hasHeaders,
        ?int $from,
        ?int $to,
        int $expectedCount,
        string $expectedFirstFirstname,
        string $expectedLastFirstname,
    ): void {
        $file = $hasHeaders ? $this->getWithHeaderFile() : $this->getWithoutHeaderFile();
        $reader = new Reader($file, hasHeaders: $hasHeaders);

        /** @var array<int, array<int|string, string|null>> $rows */
        $rows = iterator_to_array($reader->rows($from, $to));

        $firstnameKey = $hasHeaders ? 'Firstname' : 1;

        $this->assertCount($expectedCount, $rows);
        $this->assertEquals($expectedFirstFirstname, $rows[0][$firstnameKey]);
        $this->assertEquals($expectedLastFirstname, $rows[$expectedCount - 1][$firstnameKey]);
    }
}
