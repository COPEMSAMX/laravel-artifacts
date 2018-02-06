<?php

namespace Gregoriohc\Artifacts\Resources;

class Builder
{
    protected $resourceClass;

    /**
     * Builder constructor.
     * @param string $resourceClass
     */
    public function __construct($resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }

    /**
     * @return \Gregoriohc\Artifacts\Resources\Resource
     */
    public function resource()
    {
        $resourceClass = $this->resourceClass;

        return new $resourceClass;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->resource()->collectionData();
    }

    /**
     * @param mixed $value
     * @return \Illuminate\Support\Collection
     */
    public function find($value)
    {
        return $this->where($this->resource()->mainKey(), $value)->first();
    }

    /**
     * @param $arguments
     * @return \Illuminate\Support\Collection
     */
    public function where(...$arguments)
    {
        return $this->all()->where(...$arguments);
    }
}
