<?php

namespace Savvot\Random\Tests;

class XorShiftRandTest extends AbstractRandTest
{
    protected $randClass = 'Savvot\Random\XorShiftRand';

    public function testZeroHashedSeed()
    {
        /** @var \Savvot\Random\XorShiftRand $rnd */
        $rnd = new $this->randClass;

        $refClass = new \ReflectionClass($this->randClass);
        $refProp = $refClass->getProperty('hashedSeed');
        $refProp->setAccessible(true);
        $refProp->setValue($rnd, str_repeat("\0", 16));
        $refMethod = $refClass->getMethod('init');
        $refMethod->setAccessible(true);
        $refMethod->invoke($rnd);

        $state = $rnd->getState();
        $this->assertTrue(isset($state['state'], $state['state'][0], $state['state'][1]));
        $this->assertTrue($state['state'][0] > 0 && $state['state'][1] > 0);
    }
}