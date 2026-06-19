<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model;

use Inwebo\Csv\Exception\InvalidRangeException;

/**
 * The Reader class extends \SplFileObject to provide a more convenient way to read and process CSV files.
 * It streamlines data handling by allowing you to process rows as associative arrays (if the file has a header),
 * apply custom filters to skip certain rows, and use sanitizers to clean or modify data.
 * This object-oriented approach makes CSV file processing more structured and manageable.
 */
interface ReaderInterface
{
    /**
     * @throws \BadMethodCallException When the required CSV flags READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD are not included
     */
    public function setFlags(int $flags): void;

    /**
     * @return bool True if the file has headers
     */
    public function hasHeaders(): bool;

    /**
     * Returns the array of column headers. This array is populated during the constructor if $hasHeader is true.
     *
     * @return array<int, string>
     */
    public function getHeaders(): array;

    /**
     * Allows you to modify or define the name of a column using its numerical index.
     * This method is useful for CSV files without a header row,
     * where you want to assign column names to process the data as an associative array.
     */
    public function setHeader(int $index, string $colName): static;

    /**
     * Description: Reads a specific line from the CSV file.
     * You can specify a line number with the $offset or read the current line if $offset is null.
     * It applies all defined sanitizers and filters before returning the line.
     *
     * @return array<int|string, mixed>|false false at EOF
     */
    public function rowAt(?int $offset = null): array|false;

    /**
     * Returns a generator that iterates over data rows, applying filters and normalizers to each.
     * Memory usage is constant regardless of file size — rows are read one at a time.
     *
     * **Lazy evaluation**: range validation ($from/$to) runs immediately on call.
     * File iteration only starts when the generator is first consumed (foreach, current(), etc.).
     * Filters and normalizers registered after this call but before first iteration are applied.
     *
     * When $hasHeaders is true, row indices start at 1 (row 0 is the header).
     * When $hasHeaders is false, row indices start at 0.
     * $from and $to must be provided together or both null.
     *
     * @return \Generator<array<int|string, mixed>>
     *
     * @throws InvalidRangeException When only one of $from/$to is provided, or $from > $to, or $from < minimum index
     */
    public function rows(?int $from = null, ?int $to = null): \Generator;
}
