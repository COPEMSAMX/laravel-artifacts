<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Artifacts\Artifacts;

class ResourceService extends Service
{
    protected static $resourceClass;

    /**
     * @return \Gregoriohc\Artifacts\Resources\Builder
     */
    public static function query()
    {
        return call_user_func([static::resource(), 'query']);
    }

    /**
     * @return \Gregoriohc\Artifacts\Resources\Resource
     */
    public static function resource()
    {
        static::$resourceClass = static::$resourceClass ?: Artifacts::namespacedClass('resources', static::bynameStudly());

        return new static::$resourceClass();
    }

    /**
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Support\Collection
     */
    public function findAll($perPage = 10)
    {
        if (!is_null($perPage)) {
            return $this->query()->all()->forPage(request('page'), $perPage);
        }

        return $this->query()->all();
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return \Illuminate\Support\Collection
     */
    public function findBy($column, $value)
    {
        return $this->query()->where($column, $value);
    }

    /**
     * @param array $data
     * @param bool $findFirst
     * @return \Gregoriohc\Artifacts\Resources\Resource
     */
    public function create($data, $findFirst = false)
    {
        $resource = null;
        if ($findFirst) {
            $resource = $this->query()->find($data[$this->resource()->mainKey()]);
        }

        static::$resourceClass = static::$resourceClass ?: Artifacts::namespacedClass('resources', static::bynameStudly());

        return $resource ?: new static::$resourceClass($data);
    }

    /**
     * @param array $data
     * @return \Gregoriohc\Artifacts\Resources\Resource
     */
    public function firstOrCreate($data)
    {
        return $this->create($data, true);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (0 === strpos($method, 'findFirstBy')) {
            $column = snake_case(substr($method, strlen('findFirstBy')));
            return $this->findBy($column, $parameters[0])->first();
        } elseif (0 === strpos($method, 'findBy')) {
            $column = snake_case(substr($method, strlen('findBy')));
            return $this->findBy($column, $parameters[0])->all();
        }

        throw new \BadMethodCallException();
    }
}
