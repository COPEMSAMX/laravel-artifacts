<?php

namespace Gregoriohc\Artifacts\Database\Eloquent;

use \Illuminate\Database\Eloquent\Builder;

class BuilderMacros
{
    public static function boot()
    {
        /**
         * Add a relationship count / exists condition to the query.
         *
         * @param  string $relation
         * @param  string|array $scopes
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        Builder::macro('whereHasScoped', function($relation, $scopes) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            return $this->whereHas($relation, function($query) use ($relation, $scopes) {
                BuilderMacros::applyScopesToQuery($relation, $scopes, $query);
            });
        });

        /**
         * Set the relationships that should be eager loaded with scopes.
         *
         * @param  array $relations
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        Builder::macro('withScoped', function($relations) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $relations = collect($relations)->map(function($scopes, $relation) {
                return function ($query) use ($relation, $scopes) {
                    BuilderMacros::applyScopesToQuery($relation, $scopes, $query);
                };
            })->toArray();

            return $this->with($relations);
        });
    }

    /**
     * @param string|array $scopes
     * @return \Illuminate\Support\Collection
     */
    public static function parseScopes($scopes)
    {
        return collect((array) $scopes)->mapWithKeys(function($value, $key) {
            if (!is_string($key)) {
                return [$value => []];
            }
            return [$key => (array) $value];
        });
    }

    /**
     * @param string $relation
     * @param array $scopes
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public static function applyScopesToQuery($relation, $scopes, $query)
    {
        BuilderMacros::parseScopes($scopes)->each(function($parameters, $scope) use ($relation, $query) {
            try {
                $query->$scope(...$parameters);
            } catch (\BadMethodCallException $e) {
                throw new \InvalidArgumentException("The scope {$scope} does not exists in the relation {$relation}.");
            }
        });
    }
}
