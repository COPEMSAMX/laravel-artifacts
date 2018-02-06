<?php

namespace Gregoriohc\Artifacts\Database\Eloquent;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string $relation
     * @param  string|array $scopes
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function whereHasScoped($relation, $scopes)
    {
        return $this->whereHas($relation, function($query) use ($relation, $scopes) {
            $model = $this->getRelationWithoutConstraints($relation)->getModel();

            $this->parseScopes($scopes)->each(function($parameters, $scope) use ($relation, $model, $query) {
                if (!method_exists($model, 'scope'.ucfirst($scope))) {
                    throw new \InvalidArgumentException("The scope {$scope} does not exists in the relation {$relation}.");
                }
                $query->$scope(...$parameters);
            });
        });
    }

    /**
     * Set the relationships that should be eager loaded with scopes.
     *
     * @param  array $relations
     * @return $this
     */
    public function withScoped($relations)
    {
        $relations = collect($relations)->map(function($scopes, $relation) {
            return function ($query) use ($relation, $scopes) {
                $model = $this->getRelationWithoutConstraints($relation)->getModel();

                $this->parseScopes($scopes)->each(function($parameters, $scope) use ($relation, $model, $query) {
                    if (!method_exists($model, 'scope'.ucfirst($scope))) {
                        throw new \InvalidArgumentException("The scope {$scope} does not exists in the relation {$relation}.");
                    }
                    $query->$scope(...$parameters);
                });
            };
        })->toArray();

        return $this->with($relations);
    }

    /**
     * @param string|array $scopes
     * @return \Illuminate\Support\Collection
     */
    private function parseScopes($scopes)
    {
        return collect((array) $scopes)->mapWithKeys(function($value, $key) {
            if (!is_string($key)) {
                return [$value => []];
            }
            return [$key => $value];
        });
    }
}
