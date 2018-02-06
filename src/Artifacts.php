<?php

namespace Gregoriohc\Artifacts;

class Artifacts
{
    protected static $services = [];

    public static function namespacedClass($namespaceCode, $class)
    {
        return config("artifacts.namespace_{$namespaceCode}", '') . '\\' . $class;
    }

    /**
     * @return \Gregoriohc\Artifacts\Services\Service|\Gregoriohc\Artifacts\Services\ResourceService|\Gregoriohc\Artifacts\Services\ModelResourceService
     */
    public static function service($name)
    {
        $class = Artifacts::namespacedClass('services', $name . 'Service');

        return static::$services[$name] = static::$services[$name] ?: call_user_func([$class, 'instance']);
    }
}
