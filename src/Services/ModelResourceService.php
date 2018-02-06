<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Artifacts\Artifacts;
use Gregoriohc\Seedable\IsSeedable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

abstract class ModelResourceService extends ResourceService
{
    use IsSeedable;

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return call_user_func([static::resource(), 'query']);
    }

    /**
     * @return \Gregoriohc\Artifacts\Support\Concerns\IsResourceable
     */
    public static function resource()
    {
        $resourceClass = static::$resourceClass ?: Artifacts::namespacedClass('models', static::bynameStudly());

        return new $resourceClass;
    }

    /**
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function findAll($perPage = 10)
    {
        if (!is_null($perPage)) {
            return $this->query()->with($this->resource()->resourceDefaultIncludes())->paginate($perPage);
        }

        return $this->query()->get();
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findBy($column, $value)
    {
        return $this->query()->with($this->resource()->resourceDefaultIncludes())->where($column, $value);
    }

    /**
     * @param array $data
     * @param bool $findFirst
     * @return \Gregoriohc\Artifacts\Models\Model
     */
    public function create($data, $findFirst = false)
    {
        $relationships = null;
        if (isset($data['relationships']) && is_array($data['relationships'])) {
            $relationships = array_pull($data, 'relationships');
        }

        /** @var \Gregoriohc\Artifacts\Models\Model $model */
        if (false === $findFirst) {
            $model = $this->query()->create($data);
        } else {
            $findData = array_only($data, [$this->resource()->mainKey()]);
            $data = array_except($data, [$this->resource()->mainKey()]);
            $model = $this->query()->firstOrCreate($findData, $data);
        }

        if ($relationships) {
            foreach ($relationships as $name => $relationshipData) {
                if (!method_exists($model, $name)) {
                    throw new \OutOfBoundsException("The relationship '$name' does not exists.");
                }
                $modelRelationship = $model->$name();
                switch (true) {
                    case $modelRelationship instanceof BelongsTo:
                        if (!is_integer($relationshipData) && !is_null($relationshipData) ) {
                            throw new \UnexpectedValueException("The data for a 'BelongsTo' relationship must be an integer or null.");
                        }
                        if ($relationshipData) {
                            $modelRelationship->associate($relationshipData);
                        } else {
                            $modelRelationship->dissociate();
                        }
                        $model->save();
                        break;
                    case $modelRelationship instanceof HasOne:
                        if (!is_array($relationshipData)) {
                            throw new \UnexpectedValueException("The data for a 'HasOne' relationship must be an array.");
                        }
                        /** @var \Gregoriohc\Artifacts\Models\Model $relatedModel */
                        if (false === $findFirst) {
                            $modelRelationship->create($relationshipData);
                        } else {
                            $relatedModel = $modelRelationship->make();
                            $relationshipFindData = array_only($relationshipData, [$relatedModel->mainKey()]);
                            $relationshipData = array_except($relationshipData, [$relatedModel->mainKey()]);
                            $modelRelationship->firstOrCreate($relationshipFindData, $relationshipData);
                        }
                        break;
                    case $modelRelationship instanceof HasOneOrMany:
                    case $modelRelationship instanceof HasMany:
                        if (!is_array($relationshipData)) {
                            throw new \UnexpectedValueException("The data for a 'HasMany' or 'HasOneOrMany' relationship must be an array.");
                        }
                        foreach ($relationshipData as $relationshipItemData) {
                            if (false === $findFirst) {
                                $modelRelationship->create($relationshipItemData);
                            } else {
                                $relatedModel = $modelRelationship->make();
                                $relationshipItemFindData = array_only($relationshipItemData, [$relatedModel->mainKey()]);
                                $relationshipItemData = array_except($relationshipItemData, [$relatedModel->mainKey()]);
                                $modelRelationship->firstOrCreate($relationshipItemFindData, $relationshipItemData);
                            }
                        }
                        break;
                    case $modelRelationship instanceof BelongsToMany:
                        if (!is_array($relationshipData)) {
                            throw new \UnexpectedValueException("The data for a 'BelongsToMany' relationship must be an array.");
                        }
                        $modelRelationship->sync($relationshipData);
                        break;
                    default:
                        throw new \OutOfBoundsException("The relationship type '".class_basename($modelRelationship)."' is not supported.");
                        break;
                }
            }
        }

        return $model;
    }

    /**
     * @param array $item
     * @return \Gregoriohc\Artifacts\Models\Model
     */
    public function seedUpdate($item)
    {
        return $this->firstOrCreate($item);
    }

    /**
     * @param array $item
     * @return \Gregoriohc\Artifacts\Models\Model
     */
    public function seedCreate($item)
    {
        return $this->create($item);
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
            return $this->findBy($column, $parameters[0])->get();
        }

        return $this->query()->$method(...$parameters);
    }
}
