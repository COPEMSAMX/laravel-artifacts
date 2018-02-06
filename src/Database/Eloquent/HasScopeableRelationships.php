<?php

namespace Gregoriohc\Artifacts\Database\Eloquent;

trait HasScopeableRelationships
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Begin querying a model with eager loading scoped.
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withScoped($relations)
    {
        return (new static)->newQuery()->withScoped($relations);
    }
}
