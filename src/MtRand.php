<?php

namespace Savvot\Random;

/**
 * Pure PHP implementation of Mersenne twister PRNG (builtin mt_rand() function)
 * This generator is fully compatible with mt_rand() so can be used as replacement
 * Random sequences from both methods with same int seed will match perfectly
 *
 * WARNING: This generator is compatible with mt_rand ONLY when int seed is specified
 * In case of string seed (even if it is numeric) or empty (default) seed, results will be different
 *
 * Based on PHP source code from https://github.com/php/php-src/blob/master/ext/standard/rand.c
 *
 * @package Savvot\Random
 * @author  SavvoT <savvot@ya.ru>
 */
class MtRand extends AbstractRand
{
    // PHP builtin mt_rand returns 31bit integers
    const INT_MAX = 0x7fffffff;

    const N = 624;
    const M = 397;

    /**
     * @inheritdoc
     */
    public function randomInt()
    {
        if ($this->state['left'] === 0) {
            $this->reload();
        }
        $this->state['left']--;

        $s1 = $this->state['mt'][$this->state['next']];
        $this->state['next'] = (++$this->state['next']) % self::N;

        $s1 ^= ($s1 >> 11);
        $s1 ^= ($s1 << 7) & 0x9d2c5680;
        $s1 ^= ($s1 << 15) & 0xefc60000;

        return ($s1 ^ ($s1 >> 18)) >> 1;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $state = [];
        if (is_int($this->seed)) {
            // For int seed we must repeat mt_rand initialization
            $state[0] = $this->seed & 0xffffffff;
        } else {
            $st = unpack('V*', $this->hashedSeed);
            $state[0] = $st[1] ^ $st[2] ^ $st[3] ^ $st[4];
        }

        for ($i = 1; $i < self::N; $i++) {
            $r = $state[$i - 1];
            $state[$i] = (1812433253 * ($r ^ ($r >> 30)) + $i) & 0xffffffff;
        }
        $this->state = ['mt' => $state];
        $this->reload();
    }

    /**
     * "Twist" stage of algorithm
     */
    private function reload()
    {
        $p = 0;
        for ($i = self::N - self::M; $i--; ++$p) {
            $m = $this->state['mt'][$p + self::M];
            $u = $this->state['mt'][$p];
            $v = $this->state['mt'][$p + 1];
            $this->state['mt'][$p] = ($m ^ ((($u & 0x80000000) | ($v & 0x7fffffff)) >> 1) ^ (-($u & 0x00000001) & 0x9908b0df));
        }
        for ($i = self::M; --$i; ++$p) {
            $m = $this->state['mt'][$p + self::M - self::N];
            $u = $this->state['mt'][$p];
            $v = $this->state['mt'][$p + 1];
            $this->state['mt'][$p] = ($m ^ ((($u & 0x80000000) | ($v & 0x7fffffff)) >> 1) ^ (-($u & 0x00000001) & 0x9908b0df));
        }
        $m = $this->state['mt'][$p + self::M - self::N];
        $u = $this->state['mt'][$p];
        $v = $this->state['mt'][0];
        $this->state['mt'][$p] = ($m ^ ((($u & 0x80000000) | ($v & 0x7fffffff)) >> 1) ^ (-($u & 0x00000001) & 0x9908b0df));

        $this->state['left'] = self::N;
        $this->state['next'] = 0;
    }
}