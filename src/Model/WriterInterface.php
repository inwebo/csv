<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model;

use Inwebo\Csv\Exception\WriteException;

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
interface WriterInterface
{
    /**
     * Enables or disables the UTF-8 BOM prefix.
     * Must be called before the first write to take effect.
     */
    public function setIsBomEnabled(bool $isBomEnabled): static;

    public function isBomEnabled(): bool;

    /**
     * Sets the line ending written after each row.
     * Defaults to "\n" (Unix). Use "\r\n" for RFC 4180 compliance and Windows/Excel compatibility.
     */
    public function setLineEnding(string $eol): static;

    public function getLineEnding(): string;

    /**
     * Writes the header row as the first line of the CSV file.
     * If BOM is enabled, it is written before the header.
     * Filters and normalizers do not apply to the header row.
     *
     * @param array<int|string, mixed> $headers
     *
     * @throws \ValueError    if separator or enclosure is not one byte long
     * @throws \ValueError    if escape is not one byte long or the empty string
     * @throws WriteException if writing to the stream fails
     */
    public function setHeaders(array $headers): static;

    /**
     * Writes a single data row to the CSV file after applying filters and normalizers.
     * If any filter returns false, the row is skipped.
     * If BOM is enabled and no write has occurred yet, it is written first.
     *
     * @param array<int|string, mixed> $data
     *
     * @throws WriteException if writing to the stream fails
     */
    public function row(array $data): static;

    /**
     * Writes multiple rows to the CSV file.
     * Accepts any iterable, including arrays and Generators, making it suitable
     * for memory-efficient ETL pipelines (e.g., piping directly from Reader::rows()).
     *
     * @param iterable<array<int|string, mixed>> $data
     */
    public function rows(iterable $data): static;
}
