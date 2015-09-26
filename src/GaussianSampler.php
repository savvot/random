<?php

namespace Savvot\Random;

/**
 * Gaussian sampler for generating normally distributed random numbers.
 * Based on Ziggurat algorithm: https://en.wikipedia.org/wiki/Ziggurat_algorithm
 * PHP implementation based on: http://svn.code.sf.net/p/sharpneat/code/branches/V2/src/SharpNeatLib/Utility/ZigguratGaussianSampler.cs
 *
 * @package Savvot\Random
 * @author  SavvoT <savvot@ya.ru>
 */
class GaussianSampler
{
    const BLOCK_COUNT = 128;
    const R = 3.442619855899;
    const A = 9.91256303526217e-3;

    private $rng;
    private $intMaxDiv;
    private $x = [];
    private $y = [];
    private $xComp = [];
    private $ay0;

    /**
     * Creates sampler with given uniform PRNG source
     *
     * @param AbstractRand $rng Uniform PRNG
     */
    public function __construct(AbstractRand $rng)
    {
        $this->rng = $rng;
        $this->intMaxDiv = 1.0 / $rng::INT_MAX;

        $this->x[0] = self::R;
        $this->y[0] = exp(-(self::R * self::R / 2.0));

        $this->x[1] = self::R;
        $this->y[1] = $this->y[0] + (self::A / $this->x[1]);

        for ($i = 2; $i < self::BLOCK_COUNT; $i++) {
            $this->x[$i] = sqrt(-2.0 * log($this->y[$i - 1]));
            $this->y[$i] = $this->y[$i - 1] + (self::A / $this->x[$i]);
        }

        $this->x[self::BLOCK_COUNT] = 0.0;
        $this->ay0 = self::A / $this->y[0];

        $this->xComp[0] = (int)(((self::R * $this->y[0]) / self::A) * $rng::INT_MAX);
        for ($i = 1; $i < self::BLOCK_COUNT - 1; $i++) {
            $this->xComp[$i] = (int)(($this->x[$i + 1] / $this->x[$i]) * $rng::INT_MAX);
        }
        $this->xComp[self::BLOCK_COUNT - 1] = 0;
    }

    /**
     * Generates standard normally distributed random number (mean = 0.0, deviation = 1.0)
     *
     * @return float Random number
     */
    public function nextSample()
    {
        while (true) {
            $u = $this->rng->random(0, 255);
            $i = $u & 0x7f;
            $sign = (($u & 0x80) === 0) ? -1 : 1;
            $u2 = $this->rng->randomInt();

            if ($i === 0) {
                if ($u2 < $this->xComp[0]) {
                    return $u2 * $this->intMaxDiv * $this->ay0 * $sign;
                }
                return $this->sampleTail() * $sign;
            }
            if ($u2 < $this->xComp[$i]) {
                return $u2 * $this->intMaxDiv * $this->x[$i] * $sign;
            }
            $x = $u2 * $this->intMaxDiv * $this->x[$i];
            if ($this->y[$i - 1] + (($this->y[$i] - $this->y[$i - 1]) * $this->rng->randomFloat()) < exp(-($x * $x / 2.0))) {
                return $x * $sign;
            }
        }
    }

    private function sampleTail()
    {
        do {
            do {
                $f = $this->rng->randomFloat();
            } while ($f == 0);
            $x = -log($f) / self::R;

            do {
                $f = $this->rng->randomFloat();
            } while ($f == 0);
            $y = -log($f);
        } while (($y + $y) < ($x * $x));

        return self::R + $x;
    }
}