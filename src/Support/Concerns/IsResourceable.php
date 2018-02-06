<?php

namespace Gregoriohc\Artifacts\Support\Concerns;

use Gregoriohc\Artifacts\Artifacts;
use Gregoriohc\Artifacts\Http\Resources\DefaultResource;

trait IsResourceable
{
    protected static $resourceName = null;
    protected static $resourceDefaultIncludes = [];

    /**
     * @return string
     */
    public static function resourceName()
    {
        return static::$resourceName ?: static::bynameSnake();
    }

    /**
     * @return array
     */
    public static function resourceDefaultIncludes()
    {
        return static::$resourceDefaultIncludes;
    }

    /**
     * @return string
     */
    public static function transformerClass()
    {
        $class = Artifacts::namespacedClass('http_resources', static::bynameStudly(). 'Resource');

        if (!class_exists($class)) {
            $class = DefaultResource::Class;
        }

        return $class;
    }

    /**
     * @return string
     */
    public function mainKey()
    {
        return 'id';
    }
}
