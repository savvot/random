# PHP pseudo random generators library
This set of classes provides basic abstraction around different pseudo random generators with the same generic API. 
Also it contains many useful helper methods like weighted random, text generation, shuffling, array functions, etc.

WARNING: This PRNGs are **non cryptographically secure** (mt_rand() too)

[![Latest Stable Version](https://poser.pugx.org/savvot/random/v/stable)](https://packagist.org/packages/savvot/random) 
[![License](https://poser.pugx.org/savvot/random/license)](https://packagist.org/packages/savvot/random)
[![Build Status](https://travis-ci.org/savvot/random.svg?branch=master)](https://travis-ci.org/savvot/random)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/savvot/random/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/savvot/random/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/savvot/random/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/savvot/random/?branch=master)

## Why not mt_rand()? ##
PHP builtin mt_rand() and rand() are global functions, so it is not possible to create several generators with different predefined seeds and use them simultaneously.
There is also no control over current state of random sequences.

## Features ##
- Deterministic random generators with optional seeds
- Correct uniform distribution
- Gaussian sampler for normally distributed random numbers based on **Ziggurat algorithm**
- Extensible architecture: it is easy to add your own source of randomness
- Seeds can be **strings** of any length you want
- Possibility to save and restore current PRNG's state at any time, so one can **replay** the sequence from known state
- Different random types: int, float, boolean
- **Weighted random**: random array key by weights, weighted shuffle
- Random binary data generation
- Random text generation from specified characters list with optional multi-byte support
- Array methods, alternatives to array_rand, shuffle

## PRNG sources ##
- **XorShiftRand**
Fast and good quality random generator, based on XorShift+ algorithm with 128bit state. Should be used in most cases.

- **MtRand**
Pure PHP implementation of builtin mt_rand variation of Mersenne twister algorithm.
Random sequences from both methods with same int seed will match perfectly, therefore this generator is fully compatible with mt_rand() and can be used as replacement.

- **HashRand**
Proof-of-concept that md5 hash is uniformly distributed and can be used as source for pseudo random numbers. Pretty fast and straightforward.

## Examples ##
```php
//////// BASIC USAGE ////////

// Create xorshift random generator with default random seed
$rnd = new XorShiftRand();

// Default random value between 0 and GeneratorClass::INT_MAX
$value = $rnd->random();

// Random value within given range (inclusive)
$value = $rnd->random(15, 12333);

// Negative numbers allowed
$value = $rnd->random(-128, 128);

// Random unsigned int, size depends on underlying generator
$int = $rnd->randomInt();

// Random float in range 0 <= $val <= 1
$float = $rnd->randomFloat();

// True or false
$bool = $rnd->randomBool();

//////// STATE CONTROL ////////

// Read current seed
$seed = $rnd->getSeed();

// Set new seed, generator will be reinitialized
$rnd->setSeed('some new seed');

// .... several generator calls later ...

// Save state for future use
$state = $rnd->getState();

// .... several generator calls later ...

// Set saved state and restore generator to known state
$state = $rnd->setState($state); 

// .... same calls to random methods as before will produce exactly the same output

// Reset generator to initial state with current seed ('some new seed')
$rnd->reset();

//////// STRING METHODS ////////

// Generate string with 256 random bytes
$bytes = $rnd->randomData(256);

// Generate random string from default (ALNUM) characters list with length = 17
$str = $rnd->randomString(17);

// Generate random string from numbers (0-9) with length = 99
$str = $rnd->randomString(99, $rnd::CL_NUM);

// Generate lowercase hex string with length = 32
$str = $rnd->randomString(32, $rnd::CL_HEXLC);

// Generate string from custom characters list
$str = $rnd->randomString(16, 'ABCDefgh#$%^');

// If custom list contains multi-byte characters, $mb flag must be set
$str = $rnd->randomString(16, 'АБВГДЕЁЖЗ', true);

//////// ARRAY METHODS ////////

// Get random key from array (0, 1, 2 or 'four')
$key = $rnd->arrayRand([10, 20, 30, 'four' => 40]);

// Get random value from array (10, 20, 30 or 4)
$value = $rnd->arrayRandValue([10, 20, 30, 'four' => 40]);

// Shuffle array. New numeric keys will be assigned
$array = [1, 2, 'key' => 3, 4, 'assoc' => 5, 6, 7];
$rnd->arrayShuffle($array);

// Shuffle array and maintain key => value associations
$array = [1, 2, 'key' => 3, 4, 'assoc' => 5, 6, 7];
$rnd->arrayShuffleAssoc($array);

//////// WEIGHTED RANDOM ////////

// Array in "key => weight" form must be specified
$array = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'four2' => 4];

// Get random key from array by weights. 
// So 'one' will appear less likely than 'four'\'four2'
$key = $rnd->arrayWeightRand($array);

// Shuffle array using weights. 
// So 'four'\'four2' likely will appear earlier than 'one' in resulting array
$rnd->arrayWeightShuffle($array);
```

## More info ##
- PRNG: https://en.wikipedia.org/wiki/Pseudorandom_number_generator
- XorShift: https://en.wikipedia.org/wiki/Xorshift 
- Mersenne Twister: https://en.wikipedia.org/wiki/Mersenne_Twister
- Some PRNGs benchmark: http://xorshift.di.unimi.it/
- Normal distribution: https://en.wikipedia.org/wiki/Normal_distribution
- Ziggurat algorithm: https://en.wikipedia.org/wiki/Ziggurat_algorithm


