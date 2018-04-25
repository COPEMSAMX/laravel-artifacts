<?php

namespace Gregoriohc\Artifacts\Http\Resources;

use Gregoriohc\Artifacts\Support\Concerns\IsResourceable;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\Resource;

class DefaultResource extends Resource
{
    protected $skipIncludes = false;
    protected $defaultSkipAttributes = ['created_at', 'updated_at', 'deleted_at', 'pivot'];
    protected $skipAttributes = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $this->skipAttributes = $this->skipAttributes ?: $this->defaultSkipAttributes;
        foreach ($this->skipAttributes as $attribute) {
            unset($data[$attribute]);
        }

        foreach($this->resource->resourceDefaultIncludes() as $include) {
            $data[snake_case($include)] = call_user_func([$this, 'include' . $include]);
        }

        foreach (array_keys($data) as $attribute) {
            if ($this->resource->isCustomCastable($attribute)) {
                $value = $this->resource->$attribute;
                if (method_exists($value, 'toArray')) {
                    $value = $value->toArray();
                    $data[$attribute] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function skipAttributes($attributes)
    {
        $this->skipAttributes = $attributes;

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (0 == strpos($name, 'include')) {
            /** @var IsResourceable $resource */
            $relationshipName = lcfirst(substr($name, 7));
            if (method_exists($this->resource, $relationshipName)) {
                $data = $this->whenLoaded($relationshipName);

                if ($data instanceof Collection) {
                    /** @var IsResourceable $first */
                    $first = $data->first();
                    return call_user_func_array([$first ? $first->transformerClass() : DefaultResource::class, 'collection'], [$data]);
                } elseif ($data instanceof IsResourceable) {
                    return call_user_func_array([$data->transformerClass(), 'make'], [$data]);
                }

                return $data;
            }
        }
    }
}
