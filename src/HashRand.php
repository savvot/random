<?php

namespace Savvot\Random;

/**
 * Proof-of-concept that md5 hash is uniformly distributed and can be used as PRNG source
 * Pretty fast, simple and straightforward generator
 *
 * @package Savvot\Random
 * @author  SavvoT <savvot@ya.ru>
 */
class HashRand extends AbstractRand
{
    /**
     * This is 63bit generator because PHP does not support unsigned 64bit int
     */
    const INT_MAX = 0x7FFFFFFFFFFFFFFF;

    /**
     * @inheritdoc
     */
    public function randomInt()
    {
        $hash = md5($this->hashedSeed . $this->state++, true);
        $num = unpack('V*', $hash);

        // Create two 64bit numbers from four 32bit int and xor them
        return (($num[2] << 32 | $num[1]) ^ ($num[4] << 32 | $num[3])) & self::INT_MAX;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->state = 0;
    }
}