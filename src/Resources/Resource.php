<?php

namespace Gregoriohc\Artifacts\Resources;

use Gregoriohc\Artifacts\Support\Concerns\IsResourceable;

abstract class Resource
{
    use IsResourceable;

    protected $data;

    /**
     * Resource constructor.
     * @param $data
     */
    public function __construct($data = [])
    {
        $this->setData($data);
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    abstract public function collectionData();

    /**
     * @return Builder
     */
    public static function query() {
        return new Builder(static::class);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
