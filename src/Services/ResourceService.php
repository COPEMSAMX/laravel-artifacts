<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Artifacts\Artifacts;

class ResourceService extends Service
{
    /**
     * @var string
     */
    protected $resourceNamespaceCode = 'resources';

    /**
     * @var string
     */
    protected $resourceClass;

    /**
     * ResourceService constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $resource = $this->options->get('resource', static::bynameStudly());
        $this->resourceClass = Artifacts::namespacedClass($this->resourceNamespaceCode, $resource);
    }

    /**
     * @return \Gregoriohc\Artifacts\Resources\Builder
     */
    public function query()
    {
        return call_user_func([$this->resource(), 'query']);
    }

    /**
     * @return \Gregoriohc\Artifacts\Resources\Resource
     */
    public function resource()
    {
        $resourceClass = $this->resourceClass;

        return new $resourceClass();
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

        $resourceClass = $this->resourceClass;

        return $resource ?: new $resourceClass($data);
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
