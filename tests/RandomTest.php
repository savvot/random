<?php

namespace savvot\random\tests;

use savvot\random\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        // Default generator and seed
        /** @var \savvot\random\AbstractRand $rnd */
        $rnd = Random::create();
        $this->assertInstanceOf('\savvot\random\XorShiftRand', $rnd);
        $this->assertTrue(is_int($rnd->getSeed()));

        // Predefined seed and generator
        $rnd = Random::create('seeeedz', Random::MT);
        $this->assertInstanceOf('\savvot\random\MtRand', $rnd);
        $this->assertSame('seeeedz', $rnd->getSeed());

        $rnd = Random::create('some data', Random::HASH);
        $this->assertInstanceOf('\savvot\random\HashRand', $rnd);
        $this->assertSame('some data', $rnd->getSeed());

    }


    /**
     * @expectedException \savvot\random\RandException
     */
    public function testException()
    {
        $rnd = Random::create(null, __CLASS__);
    }

    public function testCreateFromState()
    {
        /** @var \savvot\random\AbstractRand $rnd */
        $rnd = Random::create(null, Random::HASH);
        $rnd->random(); $rnd->random(); $rnd->random();

        $state = $rnd->getState();
        $seq1 = [];
        for($i=0; $i<100; $i++) {
            $seq1[] = $rnd->random(0, $i);
        }

        $rnd = Random::createFromState($state);
        $this->assertInstanceOf('\savvot\random\HashRand', $rnd);

        $seq1test = [];
        for($i=0; $i<100; $i++) {
            $seq1test[] = $rnd->random(0, $i);
        }
        $this->assertSame($seq1, $seq1test);
    }
}