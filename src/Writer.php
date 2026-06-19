<?php

declare(strict_types=1);

namespace Inwebo\Csv;

use Inwebo\Csv\Exception\BadArgumentException;
use Inwebo\Csv\Exception\WriteException;
use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Filters\HasFiltersQueueInterface;
use Inwebo\Csv\Model\Filters\HasFiltersQueueTrait;
use Inwebo\Csv\Model\Normalizers\HasNormalizersQueueInterface;
use Inwebo\Csv\Model\Normalizers\HasNormalizersQueueTrait;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Model\WriterInterface;

/**
 * The Writer class extends \SplFileObject to provide a simple way to write CSV files.
 * It supports UTF-8 BOM for Excel compatibility, configurable line endings, and
 * delegates CSV control (delimiter, enclosure, escape) to the inherited setCsvControl() method.
 * Normalizers and filters follow the same FIFO pipeline as the Reader.
 *
 * Since it extends \SplFileObject, all methods to configure CSV writing (like setCsvControl) are available.
 *
 * @see \SplFileObject
 */
class Writer extends \SplFileObject implements HasFiltersQueueInterface, HasNormalizersQueueInterface, WriterInterface
{
    use HasFiltersQueueTrait;
    use HasNormalizersQueueTrait;

    private bool $bomWritten = false;
    private string $lineEnding = "\n";
    private bool $isBomEnabled = false;

    /**
     * Opens a file for writing and initializes the Writer.
     *
     * @param string $filename       The file to open or create
     * @param string $mode           [optional] The mode in which to open the file. Defaults to 'w' (truncate/create).
     *                               Use 'a' to append, 'x' to create only (fails if file exists).
     * @param bool   $useIncludePath [optional] Whether to search in the include_path for filename
     * @param bool   $isBomEnabled   [optional] Whether to prepend a UTF-8 BOM (\xEF\xBB\xBF) before the first write.
     *                               Required for correct UTF-8 rendering in Excel on Windows.
     *
     * @throws BadArgumentException When $mode is 'r' (read-only mode is not allowed on a Writer)
     * @throws \LogicException      When the filename is a directory
     * @throws \RuntimeException    When the filename cannot be opened
     */
    public function __construct(
        string $filename,
        string $mode = 'w',
        bool $useIncludePath = false,
        bool $isBomEnabled = false,
    ) {
        if ('r' === $mode) {
            throw new BadArgumentException('Mode "r" is not allowed on a Writer.');
        }
        parent::__construct($filename, $mode, $useIncludePath);
        $this->normalizersQueue = new NormalizersQueue();
        $this->filtersQueue = new FiltersQueue();
        $this->isBomEnabled = $isBomEnabled;
    }

    public function setIsBomEnabled(bool $isBomEnabled): static
    {
        $this->isBomEnabled = $isBomEnabled;

        return $this;
    }

    public function isBomEnabled(): bool
    {
        return $this->isBomEnabled;
    }

    /**
     * @throws WriteException
     */
    private function writeBOM(): void
    {
        if ($this->isBomEnabled && !$this->bomWritten) {
            $stat = $this->fstat();
            if (0 === $stat['size']) {
                if (false === @$this->fwrite("\xEF\xBB\xBF")) {
                    throw new WriteException('Failed to write UTF-8 BOM: fwrite() returned false. This occurs when the underlying stream is not writable (e.g. read-only mode) or the write operation failed at the OS level (e.g. full disk, broken pipe, or closed descriptor).');
                }
            }
        }
        $this->bomWritten = true;
    }

    public function setLineEnding(string $eol): static
    {
        $this->lineEnding = $eol;

        return $this;
    }

    public function getLineEnding(): string
    {
        return $this->lineEnding;
    }

    public function setHeaders(array $headers): static
    {
        $this->writeBOM();
        /**
         * @var array{0: string, 1: string, 2: string} $csv
         */
        $csv = $this->getCsvControl();
        if (false === @$this->fputcsv($headers, $csv[0], $csv[1], $csv[2], $this->lineEnding)) {
            throw new WriteException(sprintf('Failed to write CSV header: fputcsv() returned false. This occurs when the underlying stream write fails (e.g. stream not writable, full disk, broken pipe). CSV control — separator: "%s", enclosure: "%s", escape: "%s", line ending: "%s". Headers: %s.', $csv[0], $csv[1], $csv[2], str_replace(["\r", "\n"], ['\r', '\n'], $this->lineEnding), json_encode($headers)));
        }

        return $this;
    }

    public function row(array $data): static
    {
        $filtered = $this->filter($data);
        if (null === $filtered) {
            return $this;
        }

        $this->normalize($filtered);

        $this->writeBOM();
        /**
         * @var array{0: string, 1: string, 2: string} $csv
         */
        $csv = $this->getCsvControl();
        if (false === @$this->fputcsv($filtered, $csv[0], $csv[1], $csv[2], $this->lineEnding)) {
            throw new WriteException(sprintf('Failed to write CSV row: fputcsv() returned false. This occurs when the underlying stream write fails (e.g. stream not writable, full disk, broken pipe). CSV control — separator: "%s", enclosure: "%s", escape: "%s", line ending: "%s". Row: %s.', $csv[0], $csv[1], $csv[2], str_replace(["\r", "\n"], ['\r', '\n'], $this->lineEnding), json_encode($filtered)));
        }

        return $this;
    }

    public function rows(iterable $data): static
    {
        foreach ($data as $row) {
            $this->row($row);
        }

        return $this;
    }
}
