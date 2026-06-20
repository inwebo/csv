<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Writer;

use Inwebo\Csv\Exception\BadArgumentException;
use Inwebo\Csv\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Writer::class)]
#[Group('csv')]
#[Group('instantiate')]
class InstantiateTest extends TestCase
{
    private \SplTempFileObject $tempFile;

    public function setUp(): void
    {
        $this->tempFile = new \SplTempFileObject();
    }

    public function tearDown(): void
    {
        unset($this->tempFile);
    }

    public function testInvalidInstantiate(): void
    {
        $this->expectException(\RuntimeException::class);
        new Writer('/non/existent/path/file.csv');
    }

    public function testReadOnlyModeThrows(): void
    {
        $this->expectException(BadArgumentException::class);
        new Writer($this->tempFile->getPathname(), 'r');
    }

    public function testValidInstantiate(): void
    {
        $writer = new Writer($this->tempFile->getPathname(), 'w');
        $this->assertInstanceOf(Writer::class, $writer);
    }

    public function testInstantiateWithBOM(): void
    {
        $writer = new Writer($this->tempFile->getPathname(), 'w', isBomEnabled: true);
        $this->assertTrue($writer->isBomEnabled());
    }

    public function testInstantiateAppendMode(): void
    {
        $writer = new Writer($this->tempFile->getPathname(), 'a');
        $this->assertInstanceOf(Writer::class, $writer);
    }
}
