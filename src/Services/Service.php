<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Byname\HasByname;
use Illuminate\Config\Repository;
use Illuminate\Support\Traits\Macroable;

class Service
{
    use HasByname, Macroable;

    /**
     * @var Repository
     */
    protected $options;

    /**
     * Service constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = new Repository($options);
    }

    /**
     * @return string
     */
    protected static function bynameSuffix() {
        return 'Service';
    }
}
