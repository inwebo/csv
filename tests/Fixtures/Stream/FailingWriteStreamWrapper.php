<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Fixtures\Stream;

/**
 * Stream wrapper whose stream_write always returns false.
 * PHP converts that to -1 internally, making fwrite/fputcsv return false
 * so Writer throws WriteException on every write attempt.
 */
class FailingWriteStreamWrapper
{
    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return true;
    }

    public function stream_write(string $data): false
    {
        return false;
    }

    public function stream_read(int $count): string
    {
        return '';
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_tell(): int
    {
        return 0;
    }

    public function stream_seek(int $offset, int $whence): bool
    {
        return true;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_lock(int $operation): bool
    {
        return true;
    }

    /** @return array<string|int, int> */
    public function stream_stat(): array
    {
        return self::fakeStat();
    }

    /** @return array<string|int, int> */
    public function url_stat(string $path, int $flags): array
    {
        return self::fakeStat();
    }

    /** @return array<string|int, int> */
    private static function fakeStat(): array
    {
        return [
            0 => 0,   'dev' => 0,
            1 => 0,   'ino' => 0,
            2 => 33188, 'mode' => 33188, // 0100644 regular file
            3 => 1,   'nlink' => 1,
            4 => 0,   'uid' => 0,
            5 => 0,   'gid' => 0,
            6 => 0,   'rdev' => 0,
            7 => 0,   'size' => 0,
            8 => 0,   'atime' => 0,
            9 => 0,   'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];
    }
}
