<?php

namespace Savvot\Random\Tests;

class MtRandTest extends AbstractRandTest
{
    protected $randClass = 'Savvot\Random\MtRand';

    public function testCompatibility()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class(2015);
        mt_srand(2015);

        // Check basic sequence
        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(), $rnd->randomInt());
        }
        // Check range sequence
        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(-100, 100), $rnd->random(-100, 100));
        }

        // Seed overflow
        $rnd = new $class(PHP_INT_MAX);
        mt_srand(PHP_INT_MAX);

        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(), $rnd->randomInt());
        }
        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(-100, 100), $rnd->random(-100, 100));
        }

        // Negative seed
        $rnd = new $class(-10124499);
        mt_srand(-10124499);

        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(), $rnd->randomInt());
        }
        for($i=0; $i<100; $i++) {
            $this->assertSame(mt_rand(-100, 100), $rnd->random(-100, 100));
        }
    }
}