<?php

namespace Savvot\Random\Tests;

/**
 * Main test case
 *
 * @package Savvot\Random
 * @author  SavvoT <savvot@ya.ru>
 */

abstract class AbstractRandTest extends \PHPUnit_Framework_TestCase
{
    protected $randClass;

    public function testRandomInt()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        // Some naive tests
        $this->assertTrue(is_int($rnd->randomInt()));
        for ($i = 0; $i < 10; $i++) {
            $num = $rnd->randomInt();
            $this->assertTrue($num >= 0 && $num <= $rnd::INT_MAX);
        }

        // Lets test how "uniform" is prng distribution
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = round($rnd->randomInt() * 0.5);
        }
        // Average should be max 2% different than generator's INT_MAX/2
        $this->assertUniform($data, $rnd::INT_MAX * 0.25, $rnd::INT_MAX * 0.02);
    }

    public function testRandom()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        $this->assertSame(0, $rnd->random(0, 0));
        $this->assertSame(1, $rnd->random(1, 1));
        $this->assertSame(-1, $rnd->random(-1, -1));
        $this->assertSame($rnd::INT_MAX, $rnd->random($rnd::INT_MAX, $rnd::INT_MAX));

        $num = $rnd->random(10, 12);
        $this->assertTrue($num >= 10 && $num <= 12);
        $num = $rnd->random(-12, -5);
        $this->assertTrue($num >= -12 && $num <= -5);
        $num = $rnd->random(-100, 100);
        $this->assertTrue($num >= -100 && $num <= 100);
        $num = $rnd->random($rnd::INT_MAX - 1, $rnd::INT_MAX);
        $this->assertTrue($num >= $rnd::INT_MAX - 1 && $num <= $rnd::INT_MAX);

        // Uniform distribution test
        $data = [0, 0, 0, 0, 0];
        for ($i = 0; $i < 10000; $i++) {
            $data[$rnd->random(0, 4)]++;
        }
        // Lets compare all counts (avg count should be 10000 / 5 = 2000, 10% deviation)
        foreach ($data as $cnt) {
            $this->assertTrue(abs(2000 - $cnt) < 200);
        }
    }

    public function testRandomFloat()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        for ($i = 0; $i < 10; $i++) {
            $float = $rnd->randomFloat();
            $this->assertTrue(is_float($float));
            $this->assertTrue($float >= 0 && $float <= 1);
        }

        // Uniform distribution test
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = $rnd->randomFloat();
        }
        $this->assertUniform($data, 0.5, 0.02);
    }

    public function testRandomBool()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $this->assertTrue(is_bool($rnd->randomBool()));

        // Uniform distribution test
        $data = [0, 0];
        for ($i = 0; $i < 10000; $i++) {
            $data[$rnd->randomBool() ? 1 : 0]++;
        }
        foreach ($data as $cnt) {
            $this->assertTrue(abs(5000 - $cnt) < 200);
        }
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testRangeException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $rnd->random(10, 1);
    }

    public function testMaxException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        if ($rnd::INT_MAX < PHP_INT_MAX) {
            $this->setExpectedException('\Savvot\Random\RandException');
            $rnd->random(1, $rnd::INT_MAX + 1);
        }
    }

    public function testGaussianRandom()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        $mean = $rnd->random(1, 10);
        $deviation = $rnd->random(2, 10);

        $data = [];
        for ($i = 0; $i < 50000; $i++) {
            $data[] = $rnd->gaussianRandom($mean, $deviation);
        }
        $c = count($data);

        // Average
        $avg = array_sum($data) / $c;
        $this->assertTrue(abs($avg) < ($mean + $deviation * 0.02));

        // Median
        sort($data);
        $median = $data[(int)($c / 2)];
        $this->assertTrue(abs($median) < ($mean + $deviation * 0.02));

        // Simple probability distribution test
        $stats = [];
        foreach ($data as $num) {
            $k = ceil(abs(($num - $mean) / $deviation));
            if (!isset($stats[$k])) {
                $stats[$k] = 0;
            }
            $stats[$k]++;
        }

        $k = 100 / $c;
        $p1 = round($k * $stats[1]);
        $p2 = round($k * ($stats[2] + $stats[1]));
        $p3 = round($k * ($stats[3] + $stats[2] + $stats[1]));

        // 68% of the data should be within one standard deviation
        $this->assertTrue($p1 >= 67 && $p1 <= 69);
        // 95% of the data should be within two standard deviations
        $this->assertTrue($p2 >= 94 && $p1 <= 96);
        // 99% of the data should be within three standard deviations
        $this->assertTrue($p3 >= 98);
    }

    public function testSeed()
    {
        $class = $this->randClass;

        // Predefined
        $seed = 'random seed 1';
        $seed2 = 'seed 2';

        $rnd1 = new $class($seed);
        $rnd2 = new $class($seed);
        $rnd3 = new $class($seed2);
        $rnd4 = new $class($seed2);

        for ($i = 0; $i < 5; $i++) {
            $num1 = $rnd1->random();
            $num2 = $rnd2->random();
            $num3 = $rnd3->random();
            $num4 = $rnd4->random();
            $this->assertSame($num1, $num2);
            $this->assertSame($num3, $num4);
            $this->assertNotEquals($num1, $num3);
            $this->assertNotEquals($num2, $num4);
        }

        // Default random seed
        $rnd1 = new $class();
        $rnd2 = new $class($rnd1->getSeed());
        $rnd3 = new $class();
        $rnd4 = new $class($rnd3->getSeed());

        for ($i = 0; $i < 5; $i++) {
            $num1 = $rnd1->random();
            $num2 = $rnd2->random();
            $num3 = $rnd3->random();
            $num4 = $rnd4->random();
            $this->assertSame($num1, $num2);
            $this->assertSame($num3, $num4);
            $this->assertNotEquals($num1, $num3);
            $this->assertNotEquals($num2, $num4);
        }
    }

    public function testGetSeed()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('seed1');
        $this->assertSame('seed1', $rnd->getSeed());

        // Default seed must be random int
        $rnd = new $class();
        $this->assertTrue(is_int($rnd->getSeed()));
    }

    public function testSetSeed()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('my cool SEED');
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $data[] = $rnd->randomInt();
        }

        /** @var \Savvot\Random\AbstractRand $rnd2 */
        $rnd2 = new $class('new seed ftw');
        $data2 = [];
        for ($i = 0; $i < 10; $i++) {
            $data2[] = $rnd2->randomInt();
        }

        $rnd->setSeed('new seed ftw');
        $data3 = [];
        for ($i = 0; $i < 10; $i++) {
            $data3[] = $rnd->randomInt();
        }
        $this->assertSame($data2, $data3);

        $rnd->setSeed('my cool SEED');
        $data3 = [];
        for ($i = 0; $i < 10; $i++) {
            $data3[] = $rnd->randomInt();
        }
        $this->assertSame($data, $data3);
    }

    public function testGetState()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd1 */
        $rnd1 = new $class('seed1');
        $state1 = $rnd1->getState();
        $this->assertArrayHasKey('class', $state1);
        $this->assertArrayHasKey('seed', $state1);
        $this->assertArrayHasKey('state', $state1);
        $this->assertSame($state1['class'], $class);
        $this->assertSame($state1['seed'], $rnd1->getSeed());
        $this->assertNotNull($state1['state']);

        /** @var \Savvot\Random\AbstractRand $rnd2 */
        $rnd2 = new $class('seed1');
        $state2 = $rnd2->getState();
        $this->assertSame($state1, $state2);
        /** @var \Savvot\Random\AbstractRand $rnd3 */
        $rnd3 = new $class('new seed');
        $state3 = $rnd3->getState();
        $this->assertNotSame($state1, $state3);

        $rnd3 = clone $rnd1;
        $rnd3->random();
        $state3 = $rnd3->getState();
        $this->assertNotSame($state1, $state3);

        $rnd1->random();
        $rnd1->random();
        $rnd1->random();
        $state1 = $rnd1->getState();
        $this->assertNotSame($state1, $state2);

        $rnd2->random();
        $rnd2->random();
        $rnd2->random();
        $state2 = $rnd2->getState();

        $this->assertSame($state1, $state2);

        $rnd1 = new $class('new seed 2');
        $state1 = $rnd1->getState();
        $this->assertNotSame($state1, $state2);
    }

    public function testSetState()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd1 */
        $rnd1 = new $class('seed 1');
        $state1 = $rnd1->getState();
        $n1 = $rnd1->random();
        $n2 = $rnd1->random();
        $state2 = $rnd1->getState();
        $n3 = $rnd1->random();
        $n4 = $rnd1->random();

        $rnd1->setState($state2);
        $this->assertSame($n3, $rnd1->random());
        $this->assertSame($n4, $rnd1->random());
        $rnd1->setState($state1);
        $this->assertSame($n1, $rnd1->random());
        $this->assertSame($n2, $rnd1->random());

        /** @var \Savvot\Random\AbstractRand $rnd2 */
        $rnd2 = new $class('just another seed in the wall');
        $rnd2->random();
        $rnd2->random();
        $rnd2->random();
        $rnd2->setState($state1);
        $this->assertSame($n1, $rnd2->random());
        $this->assertSame($n2, $rnd2->random());
        $rnd2->setState($state2);
        $this->assertSame($n3, $rnd2->random());
        $this->assertSame($n4, $rnd2->random());
    }

    public function testPushPopState()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('proseed');

        $rnd->random();
        $rnd->random();
        $rnd->pushState();
        $seq1 = [];
        for ($i = 0; $i < 10; $i++) {
            $seq1[] = $rnd->random(1, 10);
        }

        $rnd->random();
        $rnd->random();
        $rnd->random();
        $rnd->pushState();
        $seq2 = [];
        for ($i = 0; $i < 23; $i++) {
            $seq2[] = $rnd->random();
        }

        $rnd->random();
        $rnd->random();
        $rnd->random();
        $rnd->popState();
        $seq2test = [];
        for ($i = 0; $i < 23; $i++) {
            $seq2test[] = $rnd->random();
        }
        $this->assertSame($seq2, $seq2test);

        $rnd->random();
        $rnd->random();
        $rnd->random();
        $rnd->popState();
        $seq1test = [];
        for ($i = 0; $i < 10; $i++) {
            $seq1test[] = $rnd->random(1, 10);
        }
        $this->assertSame($seq1, $seq1test);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testInvalidStateException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $rnd->setState(['bad key' => 'useless value']);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testWrongStateClassException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $state = $rnd->getState();
        $state['class'] = self::class;
        $rnd->setState($state);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testPopStateException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $rnd->popState();
    }

    public function testReset()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('to seed or not to seed');
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $data[] = $rnd->randomInt();
        }
        $rnd->reset();
        $data2 = [];
        for ($i = 0; $i < 10; $i++) {
            $data2[] = $rnd->randomInt();
        }
        $this->assertSame($data, $data2);
    }

    public function testRandomData()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        $data = $rnd->randomData(1);
        $this->assertTrue(is_string($data) && strlen($data) === 1);
        $data = $rnd->randomData(123);
        $this->assertTrue(is_string($data) && strlen($data) === 123);
        $data = $rnd->randomData(256000);
        $this->assertTrue(is_string($data) && strlen($data) === 256000);

        // Uniform test
        $stats = array_fill(0, 256, 0);
        for ($i = 0; $i < 256000; $i++) {
            $stats[ord($data[$i])]++;
        }
        foreach ($stats as $cnt) {
            $this->assertTrue(abs(1000 - $cnt) < 150);
        }

        // Exception test
        $this->setExpectedException('\Savvot\Random\RandException');
        $rnd->randomData(0);
    }

    public function testRandomString()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        // Default characters list
        $data = $rnd->randomString(597);
        $this->assertTrue(is_string($data) && strlen($data) == 597);
        $this->assertRegExp('@^[a-zA-Z0-9]+$@', $data);

        // Numbers only
        $data = $rnd->randomString(13, $rnd::CL_NUM);
        $this->assertTrue(is_string($data) && strlen($data) == 13);
        $this->assertRegExp('@^[0-9]+$@', $data);

        // Custom
        $data = $rnd->randomString(3, 'AAAA');
        $this->assertSame($data, 'AAA');

        // Multibyte
        $data = $rnd->randomString(597, 'АБВГДЕжзийклм', true);
        $this->assertTrue(is_string($data) && mb_strlen($data, 'UTF-8') == 597);
        $this->assertRegExp('@^[АБВГДЕжзийклм]+$@', $data);

        $data = $rnd->randomString(3, 'ЖЖЖЖЖЖЖ', true);
        $this->assertTrue(is_string($data) && mb_strlen($data, 'UTF-8') == 3);
        $this->assertSame($data, 'ЖЖЖ');

        // Uniform test
        $data = $rnd->randomString(100000, $rnd::CL_NUM);
        $stats = array_fill(0, 10, 0);
        for ($i = 0; $i < 100000; $i++) {
            $stats[$data[$i]]++;
        }
        foreach ($stats as $cnt) {
            $this->assertTrue(abs(10000 - $cnt) < 500);
        }
    }

    public function testArrayRand()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        $data = ['one', 'two', 'three', 'key' => 'four', 'five'];
        $this->assertTrue(isset($data[$rnd->arrayRand($data)]));

        // Uniform test
        $stats = [0 => 0, 1 => 0, 2 => 0, 'key' => 0, 3 => 0];
        for ($i = 0; $i < 50000; $i++) {
            $stats[$rnd->arrayRand($data)]++;
        }
        foreach ($stats as $cnt) {
            $this->assertTrue(abs(10000 - $cnt) < 500);
        }

        // Exception test
        $this->setExpectedException('\Savvot\Random\RandException');
        $data = [];
        $rnd->arrayRand($data);
    }

    public function testArrayRandValue()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('my seed is bigger than yours');

        $data = ['one', 'two', 'three', 'key' => 'four', 'five'];
        $key = $rnd->arrayRand($data);
        $rnd->reset();
        $this->assertSame($data[$key], $rnd->arrayRandValue($data));
    }

    public function testArrayShuffle()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd1 */
        $rnd1 = new $class('enlarge your seed ' . time());
        $rnd2 = clone $rnd1;

        $data = range(0, 123456);
        $data2 = $data;

        $rnd1->arrayShuffle($data);
        $rnd2->arrayShuffle($data2);

        $this->assertSame($data, $data2);
        $this->assertNotSame($data, range(0, 123456));

        $rnd2->arrayShuffle($data2);
        $this->assertNotSame($data, $data2);

        $state = $rnd1->getState();
        $data = ['1'];
        $rnd1->arrayShuffle($data);
        $this->assertSame($state, $rnd1->getState());
        $data = [];
        $rnd1->arrayShuffle($data);
        $this->assertSame($state, $rnd1->getState());

        // Uniform test
        $rnd1 = new $class();
        $data = range(0, 10);
        $stats = array_fill(0, 11, 0);
        for ($ii = 0; $ii < 1000; $ii++) {
            $rnd1->arrayShuffle($data);
            foreach ($data as $i => $v) {
                $stats[$i] += $v;
            }

        }
        foreach ($stats as $cnt) {
            $this->assertTrue(abs(5000 - $cnt) < 500);
        }
    }

    public function testArrayShuffleAssoc()
    {
        $class = $this->randClass;

        /** @var \Savvot\Random\AbstractRand $rnd1 */
        $rnd1 = new $class('fear of the seed ' . time());
        $rnd2 = clone $rnd1;

        $data = array_combine(range('A', 'Z'), range(1, 26));
        $data2 = $data;
        $expected = $data;

        $rnd1->arrayShuffleAssoc($data);
        $rnd2->arrayShuffleAssoc($data2);

        $this->assertSame($data, $data2);
        $this->assertNotSame($expected, $data);

        $rnd2->arrayShuffle($data2);
        $this->assertNotSame($data, $data2);

        $state = $rnd1->getState();
        $data = ['A' => '1'];
        $rnd1->arrayShuffle($data);
        $this->assertSame($state, $rnd1->getState());
        $data = [];
        $rnd1->arrayShuffle($data);
        $this->assertSame($state, $rnd1->getState());
    }

    public function testArrayWeightRand()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;

        $data = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'four2' => 4];
        $key = $rnd->arrayWeightRand($data);
        $this->assertTrue(isset($data[$key]));

        $stats = ['one' => 0, 'two' => 0, 'three' => 0, 'four' => 0, 'four2' => 0];
        for ($i = 0; $i < 10000; $i++) {
            $key = $rnd->arrayWeightRand($data);
            $stats[$key]++;
        }

        $sum = array_sum($data);
        $one = (1 / $sum) * 10000;
        $this->assertTrue(abs($stats['one'] - $one) < ($one / 5));
        $two = (2 / $sum) * 10000;
        $this->assertTrue(abs($stats['two'] - $two) < ($two / 7));
        $three = (3 / $sum) * 10000;
        $this->assertTrue(abs($stats['three'] - $three) < ($three / 10));
        $four = (4 / $sum) * 10000;
        $this->assertTrue(abs($stats['four'] - $four) < ($four / 10));
        $this->assertTrue(abs($stats['four2'] - $four) < ($four / 10));

        $data = ['one' => 1];
        $this->assertSame('one', $rnd->arrayWeightRand($data));
        $data = [];
        $this->assertNull($rnd->arrayWeightRand($data));

        // Disabled weights. Note: there is must be at least one non-zero weight specified
        $data = ['one' => 0, 'two' => 1, 'three' => 0];
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame('two', $rnd->arrayWeightRand($data));
        }
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testNegativeWeightsException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $data = ['one' => -1, 'two' => 2, 'three' => 3];
        $rnd->arrayWeightRand($data);
    }

    /**
     * @expectedException \Savvot\Random\RandException
     */
    public function testAllZeroWeightsException()
    {
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $this->randClass;
        $data = ['one' => 0, 'two' => 0, 'three' => 0];
        $rnd->arrayWeightRand($data);
    }

    // TODO: Uniform test
    public function testArrayWeightShuffle()
    {
        $class = $this->randClass;
        /** @var \Savvot\Random\AbstractRand $rnd */
        $rnd = new $class('fear of the seed ' . mt_rand());

        $data = array_combine(range('A', 'Z'), range(1, 26));
        $expected = $data;
        $rnd->arrayWeightShuffle($data);
        $this->assertNotSame($expected, $data);
        $this->assertEquals($expected, $data);
    }

    public function assertUniform(array $data, $targetAvg, $delta)
    {
        $avg = array_sum($data) / count($data);
        $this->assertTrue(abs($targetAvg - $avg) < $delta);
    }
}