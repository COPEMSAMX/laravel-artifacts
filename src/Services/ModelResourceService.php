<?php

namespace Gregoriohc\Artifacts\Services;

use Gregoriohc\Seedable\IsSeedable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class ModelResourceService extends ResourceService
{
    use IsSeedable;

    /**
     * @var string
     */
    protected $resourceNamespaceCode = 'models';

    /**
     * @var array
     */
    protected $includes = [];

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return call_user_func([$this->resource(), 'query']);
    }

    /**
     * @return \Gregoriohc\Artifacts\Support\Concerns\IsResourceable
     */
    public function resource()
    {
        $resourceClass = $this->resourceClass;

        return new $resourceClass;
    }

    private function processOptions($options)
    {
        if (!isset($options['filters'])) $options['filters'] = [];
        if (!isset($options['order'])) $options['order'] = [];
        if (!isset($options['order']['column'])) $options['order']['column'] = $this->resource()->mainKey();
        if (!isset($options['order']['direction'])) $options['order']['direction'] = 'asc';

        return $options;
    }

    /**
     * @param int $perPage
     * @param array $options
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function findAll($perPage = 10, $options = [])
    {
        $query = $this->queryFindAll($options);

        if (!is_null($perPage)) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryFindAll($options = [])
    {
        $options = $this->processOptions($options);

        $query = $this
            ->query()
            ->with($this->getIncludes())
            ->orderBy($options['order']['column'], $options['order']['direction']);

        if (isset($options['filters'])) {
            foreach ($options['filters'] as $filter => $filterOptions) {
                if (isset($filterOptions['value'])) {
                    $parts = explode('.', $filter);
                    $filterColumn = array_pop($parts);
                    $relation = implode('.', $parts);
                    $filterOptions['operator'] = array_get($filterOptions, 'operator', '=');
                    if (empty($relation)) {
                        $query->where($filterColumn, $filterOptions['operator'], $filterOptions['value']);
                    } else {
                        $query->whereHas($relation, function($query) use ($filterColumn, $filterOptions) {
                            $query->where($filterColumn, $filterOptions['operator'], $filterOptions['value']);
                        });
                    }
                }
            }
        }

        if (isset($options['search']) && !empty($options['search']['query'])) {
            $search = $options['search'];
            $query->where(function($query) use ($search) {
                foreach ($search['attributes'] as $attribute) {
                    if (false === strpos($attribute, '.')) {
                        $attribute = $this->resource()->getTable() . '.' . $attribute;
                    }
                    $query->orWhere($attribute, 'LIKE', "%{$search['query']}%");
                }
            });
        }

        return $query;
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findBy($column, $value)
    {
        return $this->query()->with($this->getIncludes())->where($column, $value);
    }

    /**
     * @return array
     */
    protected function getIncludes()
    {
        return array_merge(
            $this->resource()->resourceDefaultIncludes(),
            $this->includes
        );
    }

    /**
     * @param array $includes
     * @return $this
     */
    public function setIncludes($includes)
    {
        $this->includes = $includes;

        return $this;
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
     * @param \Gregoriohc\Artifacts\Models\Model $model
     * @param array $data
     * @return \Gregoriohc\Artifacts\Models\Model
     */
    public function update($model, $data)
    {
        $model->update($data);

        return $model;
    }

    /**
     * @param \Gregoriohc\Artifacts\Models\Model $model
     * @param array $data
     * @return \Gregoriohc\Artifacts\Models\Model
     */
    public function delete($model, $data)
    {
        $model->delete();

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
     * @return \Illuminate\Support\Collection
     */
    public function seedData()
    {
        return collect([]);
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
        } elseif (0 === strpos($method, 'findOrFailBy')) {
            $column = snake_case(substr($method, strlen('findOrFailBy')));
            return $this->findBy($column, $parameters[0])->firstOrFail();
        } elseif (0 === strpos($method, 'findBy')) {
            $column = snake_case(substr($method, strlen('findBy')));
            return $this->findBy($column, $parameters[0])->get();
        }

        return $this->query()->$method(...$parameters);
    }
}
