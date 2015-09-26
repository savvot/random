<?php

namespace Savvot\Random;

/**
 * Abstract base class with basic methods
 *
 * @package Savvot\Random
 * @author  SavvoT <savvot@ya.ru>
 */
abstract class AbstractRand
{
    /**
     * Maximum possible value of randomInt() generator in derived class.
     * Must be overridden to correct value if different from default uint32
     */
    const INT_MAX = 0xFFFFFFFF;

    /**
     * Helper constants with common character lists for randomString() method
     */
    const CL_ALNUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const CL_NUM = '0123456789';
    const CL_ALUC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CL_ALLC = 'abcdefghijklmnopqrstuvwxyz';
    const CL_HEXUC = '0123456789ABCDEF';
    const CL_HEXLC = '0123456789abcdef';

    /**
     * @var string User specified seed used to initialize a PRNG
     */
    protected $seed;
    /**
     * @var string 128bit binary md5 hash from seed.
     *             Should be used as data source for PRNG initialization
     */
    protected $hashedSeed;
    /**
     * @var float Helper var = 1/INT_MAX
     */
    protected $intMaxDiv;

    /**
     * @var mixed Internal generator's state.
     *            Derived class must use it for storing current state of prng.
     *            Can be saved and sets back to "replay" random sequences
     */
    protected $state;

    /**
     * @var array State stack, used by pushState() and popState() methods
     */
    protected $stateStack = [];

    /**
     * @var GaussianSampler Normal distributed random numbers generator
     */
    protected $gaussianSampler;

    /**
     * Initializes random generator
     * Must be implemented by derived class
     */
    abstract protected function init();

    /**
     * Returns random unsigned integer. Int size depends on generator's algorithm.
     * Must be implemented by derived class
     *
     * @return int Random number
     */
    abstract public function randomInt();

    /**
     * Class constructor. Initializes generator from specified $seed string
     *
     * @param string $seed Seed to initialize generator's state. Defaults to null (auto)
     */
    public function __construct($seed = null)
    {
        $this->intMaxDiv = 1.0 / static::INT_MAX;
        $this->setSeed($seed);
    }

    /**
     * Sets new seed and initializes generator
     *
     * @param string|int $seed New seed for PRNG. If null is given, creates random seed from mt_rand
     */
    public function setSeed($seed = null)
    {
        $this->seed = $seed !== null ? $seed : mt_rand();
        $this->hashedSeed = md5($this->seed, true);
        $this->init();
    }

    /**
     * Returns seed used to initialize PRNG
     *
     * @return string Seed
     */
    public function getSeed()
    {
        return $this->seed;
    }

    /**
     * Sets PRNG state, previously saved by getState() function
     *
     * @param array $state State array with seed and internal generator's state
     * @throws RandException
     */
    public function setState(array $state)
    {
        if (!isset($state['class'], $state['seed'], $state['state'])) {
            throw new RandException('Invalid state');
        }
        if ($state['class'] !== static::class) {
            throw new RandException('This state is from different generator');
        }

        $this->setSeed($state['seed']);
        $this->state = $state['state'];
    }

    /**
     * Returns array with class name, seed and current internal state
     * This state can be used to save generator's state in custom position and "replay" rnd sequences
     *
     * @return array Information needed to restore generator to known state
     */
    public function getState()
    {
        return [
            'class' => static::class,
            'seed'  => $this->seed,
            'state' => $this->state
        ];
    }

    /**
     * Pushes current internal generator's state onto stack.
     */
    public function pushState()
    {
        $this->stateStack[] = $this->getState();
    }

    /**
     * Restores last pushed internal generator's state from stack
     *
     * @throws RandException if stack is empty
     */
    public function popState()
    {
        $state = array_pop($this->stateStack);
        if ($state === null) {
            throw new RandException('State stack is empty');
        }
        $this->setState($state);
    }

    /**
     * Resets generator to initial state
     */
    public function reset()
    {
        $this->init();
    }

    /**
     * @param float $mean
     * @param float $sigma
     * @return float Normally distributed random number
     */
    public function gaussianRandom($mean = 0.0, $sigma = 1.0)
    {
        // Sampler initialization is heavy so should not be used in __construct
        if ($this->gaussianSampler === null) {
            $this->gaussianSampler = new GaussianSampler($this);
        }

        return $mean + ($this->gaussianSampler->nextSample() * $sigma);
    }

    /**
     * Returns uniformly distributed random number within given range.
     * In case of incorrect range RandException will be thrown
     *
     * @param int $min Range min. Defaults to 0.
     * @param int $max Range max. Defaults to INT_MAX
     * @return int Random number in specified range inclusive
     * @throws RandException
     */
    public function random($min = 0, $max = null)
    {
        if ($max === null) {
            $max = static::INT_MAX;
        } elseif ($max < $min) {
            throw new RandException('Max is smaller than min');
        } elseif ($max > static::INT_MAX) {
            throw new RandException('Max is bigger than maximum generator value');
        }
        return $min + (int)(($max - $min + 1) * $this->randomInt() * $this->intMaxDiv);
    }

    /**
     * Returns unsigned float
     *
     * @return float Random float value in range 0 <= num <= 1
     */
    public function randomFloat()
    {
        return $this->randomInt() * $this->intMaxDiv;
    }

    /**
     * Returns true or false
     *
     * @return bool Random boolean value
     */
    public function randomBool()
    {
        return $this->randomInt() > (static::INT_MAX >> 1);
    }

    /**
     * Returns random binary data with given length.
     * May be optimised by derived class depending on generator algorithm used
     *
     * @param int $length Length of data to generate
     * @return string Random binary data
     * @throws RandException
     */
    public function randomData($length)
    {
        static $asciiTable;
        if ($asciiTable === null) {
            $asciiTable = array_map(function ($v){ return chr($v); }, range(0, 255));
        }

        if ($length < 1) {
            throw new RandException('Invalid length');
        }

        $data = '';
        for ($i = 0; $i < $length; $i++) {
            $data .= $asciiTable[$this->random(0, 255)];
        }
        return $data;
    }

    /**
     * Generate random string with given length from specified character list.
     * Supports multi-byte characters when $mb flag is set
     *
     * @param int    $length   Length of string to generate
     * @param string $charList List of characters to create string from
     * @param bool   $mb       Whether to interpret $charList as multibyte string. Defaults to false
     * @return string Random string
     */
    public function randomString($length, $charList = self::CL_ALNUM, $mb = false)
    {
        $str = '';
        if ($mb) {
            $charList = preg_split('//u', $charList, -1, PREG_SPLIT_NO_EMPTY);
            $len = count($charList) - 1;
        } else {
            $len = strlen($charList) - 1;
        }

        for ($i = 0; $i < $length; $i++) {
            $str .= $charList[$this->random(0, $len)];
        }
        return $str;
    }

    /**
     * Returns random key from given array
     *
     * @param array $array Array to get random key from
     * @return mixed Random array's key
     * @throws RandException
     */
    public function arrayRand(array $array)
    {
        if (empty($array)) {
            throw new RandException('Empty array specified');
        }

        $keys = array_keys($array);
        $i = $this->random(0, count($keys) - 1);
        return $keys[$i];
    }

    /**
     * Returns random element from given array
     *
     * @param array $array Array to get random element from
     * @return mixed Random array's element
     */
    public function arrayRandValue(array $array)
    {
        return $array[$this->arrayRand($array)];
    }

    /**
     * Shuffles given array by reference like php shuffle() function
     * This function assigns new keys to the elements in array.
     *
     * @param array $array Array for shuffling
     */
    public function arrayShuffle(array &$array)
    {
        $len = count($array) - 1;
        if ($len <= 0) {
            return;
        }

        $array = array_values($array);
        $this->shuffle($array);
    }

    /**
     * Shuffles given array by reference like php shuffle() function
     * Preserves key => value pairs
     *
     * @param array $array Reference to array for shuffling
     */
    public function arrayShuffleAssoc(array &$array)
    {
        $len = count($array) - 1;
        if ($len <= 0) {
            return;
        }

        $keys = array_keys($array);
        $this->shuffle($keys);

        foreach ($keys as $key) {
            $tmp = $array[$key];
            unset($array[$key]);
            $array[$key] = $tmp;
        }
    }

    /**
     * Returns random key from input array by its weight
     * Array must be specified in [key => weight, ...] form
     *
     * @param array $array Input array with with key => weight elements
     * @return mixed Random key
     * @throws RandException
     */
    public function arrayWeightRand(array $array)
    {
        $count = count($array);
        if ($count <= 1) {
            return key($array);
        }
        $sum = array_sum($array);
        if ($sum < 1) {
            throw new RandException('Negative or all-zero weights not allowed');
        }
        $targetWeight = $this->random(1, $sum);
        foreach ($array as $key => $weight) {
            if ($weight < 0) {
                throw new RandException('Negative weights not allowed');
            }
            $targetWeight -= $weight;
            if ($targetWeight <= 0) {
                return $key;
            }
        }
    }

    /**
     * Shuffles input array by element's weights.
     * Array must be specified in [key => weight, ...] form
     *
     * @param array $array Array for shuffling
     */
    public function arrayWeightShuffle(array &$array)
    {
        $tmp = [];
        $c = count($array);

        for ($i = 0; $i < $c; $i++) {
            $key = $this->arrayWeightRand($array);
            $tmp[$key] = $array[$key];
            unset($array[$key]);
        }
        $array = $tmp;
    }

    /**
     * Simple array shuffle helper function
     *
     * @param array $array
     */
    private function shuffle(array &$array)
    {
        for ($i = count($array) - 1; $i >= 0; $i--) {
            $j = $this->random(0, $i);
            $tmp = $array[$i];
            $array[$i] = $array[$j];
            $array[$j] = $tmp;
        }
    }
}