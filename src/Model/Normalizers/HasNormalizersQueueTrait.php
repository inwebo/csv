<?php

declare(strict_types=1);

namespace Inwebo\Csv\Model\Normalizers;

trait HasNormalizersQueueTrait
{
    /**
     * @var NormalizersQueue<callable(array<int|string, mixed>):void>
     */
    private NormalizersQueue $normalizersQueue;

    public function getNormalizersQueue(): NormalizersQueue
    {
        return $this->normalizersQueue;
    }

    public function pushNormalizer(callable $callable): static
    {
        $this->normalizersQueue->push($callable);

        return $this;
    }

    public function clearNormalizers(): static
    {
        $this->normalizersQueue->clear();

        return $this;
    }

    /**
     * @param array<int|string, mixed> $row
     */
    protected function normalize(array &$row): void
    {
        if (count($this->normalizersQueue) > 0) {
            foreach ($this->normalizersQueue as $normalizer) {
                $normalizer($row);
            }
        }
    }
}
