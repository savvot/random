<?php

namespace savvot\random;

/**
 * Static helper class.
 * Provides uniform way to create RNG objects from different generators and states
 *
 * @package savvot\random
 * @author  SavvoT <savvot@ya.ru>
 */
class Random
{
    const MT = '\savvot\random\MtRand';
    const XORSHIFT = '\savvot\random\XorShiftRand';
    const HASH = '\savvot\random\HashRand';

    /**
     * Creates RNG from specified class and initializes it with given seed
     *
     * @param string $seed     Seed to initialize generator's state. Defaults to null (auto)
     * @param string $rngClass Generator's class. Must be the child of AbstractRand. Defaults to XorShiftRand
     * @return AbstractRand Newly created PRNG
     * @throws RandException if generator class is invalid
     */
    public static function create($seed = null, $rngClass = self::XORSHIFT)
    {
        if (!is_subclass_of($rngClass, '\savvot\random\AbstractRand')) {
            throw new RandException('PRNG class must extend AbstractRand');
        }

        return new $rngClass($seed);
    }

    /**
     * Creates RNG from previously saved state
     *
     * @param array $state State array created with getState() method
     * @return AbstractRand Newly created PRNG
     * @throws RandException if specified state is invalid
     */
    public static function createFromState(array $state)
    {
        if (!isset($state['class'], $state['seed'], $state['state'])) {
            throw new RandException('Invalid state');
        }

        $class = $state['class'];
        if (!is_subclass_of($class, '\savvot\random\AbstractRand')) {
            throw new RandException('Invalid rng class in state');
        }

        /** @var \savvot\random\AbstractRand $prng */
        $prng = new $class;
        $prng->setState($state);

        return $prng;
    }
}