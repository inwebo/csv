<?php

declare(strict_types=1);

namespace Inwebo\Csv;

use Inwebo\Csv\Exception\InvalidRangeException;
use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Filters\HasFiltersQueueInterface;
use Inwebo\Csv\Model\Filters\HasFiltersQueueTrait;
use Inwebo\Csv\Model\Normalizers\HasNormalizersQueueInterface;
use Inwebo\Csv\Model\Normalizers\HasNormalizersQueueTrait;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Model\ReaderInterface;

/**
 * The Reader class extends \SplFileObject to provide a more convenient way to read and process CSV files.
 * It streamlines data handling by allowing you to process rows as associative arrays (if the file has a header),
 * apply custom filters to skip certain rows, and use sanitizers to clean or modify data.
 * This object-oriented approach makes CSV file processing more structured and manageable.
 */
class Reader extends \SplFileObject implements HasFiltersQueueInterface, HasNormalizersQueueInterface, ReaderInterface
{
    use HasFiltersQueueTrait;
    use HasNormalizersQueueTrait;

    private const REQUIRED_FLAGS = \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD;

    /** @var array<int, string> */
    private array $headers = [];

    private readonly bool $hasHeaders;

    /**
     * Creates a new instance of the Reader class and initializes the CSV file for processing.
     * It sets the file's flags to \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD for proper CSV parsing.
     * This means the file is read as CSV, empty lines are skipped, newlines at the end of lines iis removed, and the file is read ahead.
     * The instance remains configurable via \SplFileObject methods (e.g. setCsvControl). Calling setFlags() is allowed only if READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD are preserved.
     * If the $hasColName parameter is true, it reads the first row of the file to use as column headers for subsequent rows.
     *
     * @param string $filename       The file to open
     * @param bool   $useIncludePath [optional] Whether to search in the include_path for filename
     * @param bool   $hasHeaders     [optional] parameter is true, it reads the first row of the file to use as column headers for subsequent rows
     *
     * @throws \LogicException   When the filename is a directory
     * @throws \RuntimeException When the filename cannot be opened
     *
     * @see SplFileObject
     */
    public function __construct(
        string $filename,
        bool $useIncludePath = false,
        bool $hasHeaders = true,
    ) {
        parent::__construct($filename, 'r', $useIncludePath);
        $this->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD);

        $this->normalizersQueue = new NormalizersQueue();
        $this->filtersQueue = new FiltersQueue();
        $this->hasHeaders = $hasHeaders;

        if (true === $this->hasHeaders) {
            /** @var array<int, string>|false|string $colName */
            $colName = $this->current();

            if (false !== $colName && !is_string($colName)) {
                $this->headers = $colName;
            }
        }
    }

    public function setFlags(int $flags): void
    {
        if ((self::REQUIRED_FLAGS & $flags) !== self::REQUIRED_FLAGS) {
            throw new \BadMethodCallException(sprintf('Reader requires flags READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD. Missing: 0x%x', self::REQUIRED_FLAGS & ~$flags));
        }

        parent::setFlags($flags);
    }

    public function hasHeaders(): bool
    {
        return $this->hasHeaders;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

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
     * Get the relative offset for the given offset, accounting for headers.
     */
    protected function getRelativeOffset(int $offset): int
    {
        return ($this->hasHeaders()) ? $offset - 1 : $offset;
    }

    /**
     * Validate the range input for the row's generator.
     *
     * @throws InvalidRangeException
     */
    protected function validateInput(?int $from = null, ?int $to = null): void
    {
        if (null === $from && is_int($to)) {
            throw new InvalidRangeException('The $to parameter must be null when $from is null');
        }

        if (null === $to && is_int($from)) {
            throw new InvalidRangeException('The $from parameter must be null when $to is null');
        }
        if (is_int($from)) {
            $minimum = $this->hasHeaders ? 1 : 0;
            if ($from < $minimum) {
                throw new InvalidRangeException(sprintf('The $from parameter must be >= %d', $minimum));
            }
        }

        if (is_int($to) && $from > $to) {
            throw new InvalidRangeException('The $from parameter must be less than or equal to $to');
        }
    }

    public function rows(?int $from = null, ?int $to = null): \Generator
    {
        $this->validateInput($from, $to);

        $this->rewind();
        if ($this->hasHeaders) {
            $this->current(); // position READ_AHEAD buffer past the header
        }

        if (null !== $from) {
            $this->seek($this->getRelativeOffset($from));
        }
        $remaining = (null !== $from && null !== $to) ? $to - $from + 1 : null;

        while ($this->valid()) {
            $row = $this->rowAt(null);

            if (false !== $row) {
                yield $row;
            }

            if (null !== $remaining) {
                --$remaining;
                if ($remaining <= 0) {
                    break;
                }
            }

            if (!$this->hasHeaders) {
                $this->next();
            }
        }
    }
}
