<?php

namespace App\Model;

readonly class Bounds
{
    public function __construct(
        private float $lowerBound,
        private float $upperBound
    )
    {}

    public function lowerBound(): float
    {
        return $this->lowerBound;
    }

    public function upperBound(): float
    {
        return $this->upperBound;
    }
}
