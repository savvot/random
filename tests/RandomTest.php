<?php

namespace Savvot\Random\Tests;

use Savvot\Random\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        // Default generator and seed
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = Random::create();
        $this->assertInstanceOf('\Savvot\Random\XorShiftRand', $rnd);
        $this->assertTrue(is_int($rnd->getSeed()));

        // Predefined seed and generator
        $rnd = Random::create('seeeedz', Random::MT);
        $this->assertInstanceOf('\Savvot\Random\MtRand', $rnd);
        $this->assertSame('seeeedz', $rnd->getSeed());

        $rnd = Random::create('some data', Random::HASH);
        $this->assertInstanceOf('\Savvot\Random\HashRand', $rnd);
        $this->assertSame('some data', $rnd->getSeed());

    }


    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testCreateException()
    {
        $rnd = Random::create(null, __CLASS__);
    }

    public function testCreateFromState()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = Random::create(null, Random::HASH);
        $rnd->random(); $rnd->random(); $rnd->random();

        $state = $rnd->getState();
        $seq1 = [];
        for($i=0; $i<100; $i++) {
            $seq1[] = $rnd->random(0, $i);
        }

        $rnd = Random::createFromState($state);
        $this->assertInstanceOf('\Savvot\Random\HashRand', $rnd);

        $seq1test = [];
        for($i=0; $i<100; $i++) {
            $seq1test[] = $rnd->random(0, $i);
        }
        $this->assertSame($seq1, $seq1test);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testBadStateException()
    {
        $rnd = Random::createFromState(['bad' => 'state']);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testBadStateClassException()
    {
        $rnd = Random::createFromState(['class' => __CLASS__]);
    }
}