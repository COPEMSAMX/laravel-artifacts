<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Byname\HasByname;
use Illuminate\Support\Traits\Macroable;

abstract class Service
{
    use HasByname, Macroable;

    /**
     * @return string
     */
    protected static function bynameSuffix() {
        return 'Service';
    }

    /**
     * @return static
     */
    public static function instance()
    {
        return new static();
    }
}
