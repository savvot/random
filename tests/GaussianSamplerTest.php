<?php

namespace Savvot\Random\Tests;

use Savvot\Random\GaussianSampler;
use Savvot\Random\Random;

class GaussianSamplerTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $sampler = new GaussianSampler(Random::create());
        $this->assertInstanceOf('\Savvot\Random\GaussianSampler', $sampler);

        // Only AbstractRand children allowed
        $error = PHP_MAJOR_VERSION == 7 ? '\TypeError' : '\PHPUnit_Framework_Error';
        $this->setExpectedException($error);
        $sampler = new GaussianSampler(new \stdClass);
    }

    public function testNextSample()
    {
        $sampler = new GaussianSampler(Random::create());

        $data = [];
        for ($i = 0; $i < 50000; $i++) {
            $data[] = $sampler->nextSample();
        }
        $c = count($data);

        // Average
        $avg = array_sum($data) / $c;
        $this->assertTrue(abs($avg) < 0.02);

        // Median
        sort($data);
        $median = $data[(int)($c / 2)];
        $this->assertTrue(abs($median) < 0.02);

        // Mode
        $stats = [];
        foreach ($data as $num) {
            $k = (string)(0.0 + sprintf('%.1f', $num));
            if (!isset($stats[$k])) {
                $stats[$k] = 0;
            }
            $stats[$k]++;
        }
        arsort($stats);
        $key = (float)key($stats);
        $this->assertTrue($key >= -0.2 && $key <= 0.2);
        $this->assertTrue($stats['0'] > $stats['3']);

        $a1 = $stats['0.1'] + $stats['-0.1'] + $stats['0.2'] + $stats['-0.2'] + $stats['0.3'] + $stats['-0.3'];
        $a2 = $stats['0.4'] + $stats['-0.4'] + $stats['0.5'] + $stats['-0.5'] + $stats['0.6'] + $stats['-0.6'];
        $this->assertTrue($a1 > $a2);

        // Simple probability distribution test
        $stats = [];
        foreach ($data as $num) {
            $k = ceil(abs($num));
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
}