<?php

declare(strict_types=1);

namespace Inwebo\Csv\Tests\Fixtures\Model;

use Inwebo\Csv\Reader;

/**
 * Intercepts seek() calls to verify O(N) complexity invariants.
 * rows() must seek at most once regardless of the range size.
 */
class SpyReader extends Reader
{
    public int $seekCount = 0;

    public function seek(int $line): void
    {
        ++$this->seekCount;
        parent::seek($line);
    }
}
