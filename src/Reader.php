<?php

declare(strict_types=1);

namespace Inwebo\Csv;

use Inwebo\Csv\Model\FiltersQueue;
use Inwebo\Csv\Model\NormalizersQueue;

/**
 * The Reader class extends \SplFileObject to provide a more convenient way to read and process CSV files.
 * It streamlines data handling by allowing you to process rows as associative arrays (if the file has a header),
 * apply custom filters to skip certain rows, and use sanitizers to clean or modify data.
 * This object-oriented approach makes CSV file processing more structured and manageable.
 */
class Reader extends \SplFileObject
{
    /** @var array<int, string> */
    private array $headers = [];

    /**
     * @var NormalizersQueue<callable(array<int|string, ?string> &$row):void>
     */
    private NormalizersQueue $normalizersQueue;

    /**
     * @var FiltersQueue<callable(array<int|string, ?string>):bool>
     */
    private FiltersQueue $filtersQueue;

    /**
     * Creates a new instance of the Reader class and initializes the CSV file for processing.
     * It sets the file's flags to \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD for proper CSV parsing.
     * This means the file is read as CSV, empty lines are skipped, newlines at the end of lines are removed, and the file is read ahead.
     * The instance remains fully configurable using all \SplFileObject methods (e.g. setCsvControl, setFlags).
     * If the $hasColName parameter is true, it reads the first row of the file to use as column headers for subsequent rows.
     *
     * @param string   $filename       The file to open
     * @param string   $mode           [optional] The mode in which to open the file. See {@see fopen} for a list of allowed modes.
     * @param bool     $useIncludePath [optional] Whether to search in the include_path for filename
     * @param resource $context        [optional] A valid context resource created with {@see stream_context_create}
     * @param bool     $hasHeaders     [optional] parameter is true, it reads the first row of the file to use as column headers for subsequent rows
     *
     * @throws \LogicException   When the filename is a directory
     * @throws \RuntimeException When the filename cannot be opened
     *
     * @see SplFileObject
     */
    public function __construct(
        string $filename,
        string $mode = 'r',
        bool $useIncludePath = false,
        mixed $context = null,
        private readonly bool $hasHeaders = true,
    ) {
        parent::__construct($filename, $mode, $useIncludePath, $context);
        $this->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD);

        $this->normalizersQueue = new NormalizersQueue();
        $this->filtersQueue = new FiltersQueue();

        if (true === $this->hasHeaders) {
            /** @var array<int, string>|false|string $colName */
            $colName = $this->current();

            if (false !== $colName && !is_string($colName)) {
                $this->headers = $colName;
            }
        }
    }

    /**
     * @return NormalizersQueue<callable(array<int|string, ?string> &$row):void>
     */
    public function getNormalizersQueue(): NormalizersQueue
    {
        return $this->normalizersQueue;
    }

    /**
     * @return FiltersQueue<callable(array<int|string, ?string>):bool>
     */
    public function getFiltersQueue(): FiltersQueue
    {
        return $this->filtersQueue;
    }

    /**
     * @return bool True if the file has headers
     */
    public function hasHeaders(): bool
    {
        return $this->hasHeaders;
    }

    /**
     * Returns the array of column headers. This array is populated during the constructor if $hasHeader is true.
     *
     * @return array<int, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Allows you to modify or define the name of a column using its numerical index.
     * This method is useful for CSV files without a header row,
     * where you want to assign column names to process the data as an associative array.
     */
    public function setHeader(int $index, string $colName): static
    {
        $this->headers[$index] = $colName;

        return $this;
    }

    /**
     * Converts an indexed array of data into an associative array using the column names defined in the $header property.
     * If an index does not have a corresponding column name, its value is omitted from the resulting associative array.
     *
     * @param array<int|string, mixed> $row
     *
     * @return array<int|string, mixed>
     */
    protected function setHeadersRow(array $row): array
    {
        if (empty($this->headers)) {
            return $row;
        }

        $buffer = [];
        foreach ($this->headers as $index => $colName) {
            $buffer[$colName] = $row[$index] ?? null;
        }

        return $buffer;
    }

    /**
     * Description: Reads a specific line from the CSV file.
     * You can specify a line number with the $offset or read the current line if $offset is null.
     * It applies all defined sanitizers and filters before returning the line.
     *
     * @return array<int|string, mixed>|false false at EOF
     */
    public function rowAt(?int $offset = null): array|false
    {
        if (null !== $offset) {
            $this->seek($offset);
        }

        /** @var array<int|string, mixed>|false $row */
        $row = ($this->hasHeaders) ? $this->fgetcsv(escape: '\\') : $this->current();
        if (false !== $row) {
            $row = $this->setHeadersRow($row);

            $filteredLine = $this->filter($row);

            if (null !== $filteredLine) {
                $this->normalize($filteredLine);

                return $filteredLine;
            }

            return false;
        }

        return $row;
    }

    /**
     * Clear all registered normalizers.
     *
     * @return $this
     */
    public function clearNormalizers(): self
    {
        $this->normalizersQueue->clear();

        return $this;
    }

    /**
     * Adds a callable function to the list of sanitizers. This function will be applied to every line read to clean or modify its data.
     * C'est un normalizer.
     *
     * @param callable(array<int|string, ?string> &$row):void $callable
     *
     * @return $this
     */
    public function pushNormalizer(callable $callable): self
    {
        $this->normalizersQueue->push($callable);

        return $this;
    }

    /**
     * Applies all registered sanitizer functions to the given line. This method is used internally by lineAt and modifies the $row array in place.
     *
     * @param array<int|string, mixed> $row
     */
    protected function normalize(array &$row): void
    {
        if ($this->normalizersQueue->isNotEmpty()) {
            $this->normalizersQueue->rewind();
            while ($this->normalizersQueue->valid()) {
                $this->normalizersQueue->normalize($row);
                $this->normalizersQueue->next();
            }
        }
    }

    /**
     * Clear all registered filters.
     *
     * @return $this
     */
    public function clearFilters(): self
    {
        $this->filtersQueue->clear();

        return $this;
    }

    /**
     * Adds a callable function to the list of filters.
     * This function will be applied to every line to determine if it should be included in the results.
     *
     * @param callable(array<int|string, mixed> $row):bool $callable
     */
    public function pushFilter(callable $callable): self
    {
        $this->filtersQueue->push($callable);

        return $this;
    }

    /**
     * Applies all registered filter functions to the given line.
     * If any of the filter functions returns false, the line is considered invalid, and the method returns null.
     *
     * @param array<int|string, mixed> $row
     *
     * @return array<int|string, mixed>|null
     */
    protected function filter(array $row): ?array
    {
        if ($this->filtersQueue->isNotEmpty()) {
            $this->filtersQueue->rewind();
            while ($this->filtersQueue->valid()) {
                if (!$this->filtersQueue->filter($row)) {
                    return null;
                }
                $this->filtersQueue->next();
            }
        }

        return $row;
    }

    /**
     * Get the relative offset for the given offset, accounting for headers.
     */
    protected function getRelativeOffset(int $offset): int
    {
        return ($this->hasHeaders()) ? $offset - 1 : $offset;
    }

    /**
     * Validate the range input for the row's generator.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateInput(?int $from = null, ?int $to = null): void
    {
        if (null === $from && is_int($to)) {
            throw new \InvalidArgumentException('The $to parameter must be null when $from is null');
        }

        if (null === $to && is_int($from)) {
            throw new \InvalidArgumentException('The $from parameter must be null when $to is null');
        }
        // Pas sur
        if (is_int($from)) {
            $minimum = $this->hasHeaders ? 1 : 0;
            if ($from < $minimum) {
                throw new \InvalidArgumentException(sprintf('The $from parameter must be >= %d', $minimum));
            }
        }

        if (is_int($to) && $from > $to) {
            throw new \InvalidArgumentException('The $from parameter must be less than or equal to $to');
        }
    }

    /**
     * Provides a generator to iterate over the lines of the file.
     * It reads each line one by one, applying filters and sanitizers, and yields the valid lines.
     * This is the most memory-efficient way to read large files.
     *
     * @return \Generator<array<int|string, mixed>>
     */
    public function rows(?int $from = null, ?int $to = null): \Generator
    {
        $this->validateInput($from, $to);

        if (null === $from) {
            $this->rewind();
            if ($this->hasHeaders) {
                $this->current(); // position READ_AHEAD buffer past the header
            }
        }

        $offset = (null !== $from) ? $this->getRelativeOffset($from) : null;

        while ($this->valid()) {
            $row = $this->rowAt($offset);

            if (false !== $row) {
                yield $row;
            }

            if (null !== $offset) {
                ++$offset;

                if ($offset >= $to) {
                    break;
                }
            } elseif (!$this->hasHeaders) {
                $this->next();
            }
        }
    }
}
