<?php

declare(strict_types=1);

/**
 * CSV Reader benchmark suite — V1 vs V2 comparison workflow:
 *
 *   1. git checkout master          # V1 code
 *      composer phpbench -- --tag=v1
 *
 *   2. git checkout develop         # V2 code
 *      composer phpbench-compare    # runs with --ref=v1 --report=compare
 *
 * The .phpbench/ storage directory is gitignored and persists across branch switches,
 * so the V1 baseline remains available when running the comparison on develop.
 *
 * Available composer commands:
 *   composer phpbench              # run + aggregate report (console)
 *   composer phpbench-html         # run + HTML report in benchmark-results/
 *   composer phpbench-compare      # run + compare against v1 tag
 */

use Inwebo\Csv\Reader;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[BeforeMethods('setUp')]
class CsvBench
{
    private const SIZES = [
        'small'  => 1_000,
        'medium' => 10_000,
        'large'  => 100_000,
    ];

    private const COLUMNS = ['Id', 'Firstname', 'Lastname', 'Email', 'City'];

    private string $file;
    private int $rowCount;

    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------

    public function setUp(array $params): void
    {
        $size = $params['size'];
        $this->rowCount = self::SIZES[$size];
        $this->file = $this->getOrCreateFixture($size, $this->rowCount);
    }

    private function getOrCreateFixture(string $name, int $rowCount): string
    {
        $path = __DIR__ . '/fixtures/' . $name . '.csv';

        if (file_exists($path)) {
            return $path;
        }

        $handle = fopen($path, 'w');
        assert(false !== $handle);

        fputcsv($handle, self::COLUMNS, escape: '\\');
        for ($i = 1; $i <= $rowCount; ++$i) {
            fputcsv($handle, [
                (string) $i,
                "Firstname{$i}",
                "Lastname{$i}",
                "user{$i}@example.com",
                "City{$i}",
            ], escape: '\\');
        }

        fclose($handle);

        return $path;
    }

    // -------------------------------------------------------------------------
    // Providers
    // -------------------------------------------------------------------------

    /** @return \Generator<string, array{size: string}> */
    public function provideFiles(): \Generator
    {
        yield 'small'  => ['size' => 'small'];
        yield 'medium' => ['size' => 'medium'];
        yield 'large'  => ['size' => 'large'];
    }

    // -------------------------------------------------------------------------
    // Scenarios
    // -------------------------------------------------------------------------

    /**
     * Basic unbounded iteration — measures pure row-reading cost.
     */
    #[Revs(3)]
    #[Iterations(3)]
    #[Warmup(1)]
    #[ParamProviders('provideFiles')]
    public function benchBasicRead(array $params): void
    {
        $reader = new Reader($this->file);
        foreach ($reader->rows() as $row) {
        }
    }

    /**
     * Unbounded iteration with 1 filter — measures filter pipeline overhead.
     * The filter always passes (non-empty Id) so all rows are yielded.
     */
    #[Revs(3)]
    #[Iterations(3)]
    #[Warmup(1)]
    #[ParamProviders('provideFiles')]
    public function benchFiltering(array $params): void
    {
        $reader = new Reader($this->file);
        $reader->pushFilter(fn (array $row): bool => !empty($row['Id']));

        foreach ($reader->rows() as $row) {
        }
    }

    /**
     * Unbounded iteration with 1 normalizer — measures normalizer pipeline overhead.
     */
    #[Revs(3)]
    #[Iterations(3)]
    #[Warmup(1)]
    #[ParamProviders('provideFiles')]
    public function benchNormalization(array $params): void
    {
        $reader = new Reader($this->file);
        $reader->pushNormalizer(function (array &$row): void {
            $row = array_map('trim', $row);
        });

        foreach ($reader->rows() as $row) {
        }
    }

    /**
     * Bounded read covering the second half of the file — stresses the single-seek O(N) implementation.
     * A regression to O(N²) would appear clearly here, especially on the large fixture.
     */
    #[Revs(3)]
    #[Iterations(3)]
    #[Warmup(1)]
    #[ParamProviders('provideFiles')]
    public function benchLargeFile(array $params): void
    {
        $reader = new Reader($this->file);
        $from = (int) ($this->rowCount / 2);

        foreach ($reader->rows($from, $this->rowCount) as $row) {
        }
    }
}
