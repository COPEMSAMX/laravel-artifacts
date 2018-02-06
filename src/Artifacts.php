<?php

namespace Gregoriohc\Artifacts;

use Gregoriohc\Artifacts\Services\Service;
use Gregoriohc\Artifacts\Services\ResourceService;
use Gregoriohc\Artifacts\Services\ModelResourceService;

class Artifacts
{
    protected static $services = [];

    public static function namespacedClass($namespaceCode, $class)
    {
        return config("artifacts.namespace_{$namespaceCode}", '') . '\\' . $class;
    }

    /**
     * @param string $name
     * @return Services\ModelResourceService|Services\ResourceService|Services\Service
     */
    public static function service($name)
    {
        if (array_has(static::$services, $name)) {
            return static::$services[$name];
        }

        $class = Artifacts::namespacedClass('services', $name . 'Service');
        if (class_exists($class)) {
            return static::$services[$name] = new $class();
        }

        $modelClass = Artifacts::namespacedClass('models', $name);
        if (class_exists($modelClass)) {
            return static::$services[$name] = new ModelResourceService(['resource' => $name]);
        }

        $resourceClass = Artifacts::namespacedClass('resources', $name);
        if (class_exists($resourceClass)) {
            return static::$services[$name] = new ResourceService(['resource' => $name]);
        }

        return static::$services[$name] = new Service();
    }
}
