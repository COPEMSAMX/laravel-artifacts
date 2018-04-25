<?php

namespace Gregoriohc\Artifacts\Support\Concerns;

use ReflectionClass;

/**
 * Constants trait for getting classes constants
 *
 * Inspired in Sebastiaan Luca's constants trait (https://github.com/sebastiaanluca/laravel-helpers/blob/master/src/Classes/Constants.php)
 */
trait Constants
{
    /**
     * Returns class constants, optionally filtered by given prefix
     *
     * @param string|null $prefix
     * @return array
     * @throws \ReflectionException
     */
    public static function constants($prefix = null)
    {
        $constants = (new ReflectionClass(static::class))->getConstants();

        if (!is_null($prefix)) {
            $constants = array_filter($constants, function ($key) use ($prefix) {
                return stripos($key, $prefix) === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $constants;
    }

    /**
     * Returns class constants values, optionally filtered by given prefix
     *
     * @param string|null $prefix
     * @return array
     * @throws \ReflectionException
     */
    public static function constantsValues($prefix = null)
    {
        return array_values(static::constants($prefix));
    }
}