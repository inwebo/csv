<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Exception;

use Inwebo\Csv\Exception\WriteException;
use Inwebo\Csv\Model\Filters\FiltersQueue;
use Inwebo\Csv\Model\Normalizers\NormalizersQueue;
use Inwebo\Csv\Tests\Fixtures\Stream\FailingWriteStreamWrapper;
use Inwebo\Csv\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Writer::class)]
#[UsesClass(WriteException::class)]
#[UsesClass(FiltersQueue::class)]
#[UsesClass(NormalizersQueue::class)]
class WriterExceptionTest extends TestCase
{
    private const SCHEME = 'failwrite';

    public function setUp(): void
    {
        stream_wrapper_register(self::SCHEME, FailingWriteStreamWrapper::class);
    }

    public function tearDown(): void
    {
        stream_wrapper_unregister(self::SCHEME);
    }

    private function makeWriter(bool $isBomEnabled = false): Writer
    {
        return new Writer(self::SCHEME.'://test', 'w', isBomEnabled: $isBomEnabled);
    }

    public function testRowWriteFailureThrowsWriteException(): void
    {
        $writer = $this->makeWriter();
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Failed to write CSV row:');
        $writer->row(['id', 'name']);
    }

    public function testHeaderWriteFailureThrowsWriteException(): void
    {
        $writer = $this->makeWriter();
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Failed to write CSV header:');
        $writer->setHeaders(['Id', 'Name']);
    }

    public function testBomWriteFailureThrowsWriteException(): void
    {
        $writer = $this->makeWriter(isBomEnabled: true);
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Failed to write UTF-8 BOM:');
        $writer->row(['id', 'name']);
    }
}
