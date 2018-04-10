<?php

namespace Gregoriohc\Artifacts\Models;

use Gregoriohc\Byname\HasByname;
use Gregoriohc\Artifacts\Support\Concerns\IsResourceable;
use Gregoriohc\Castable\HasCustomCasts;
use Spatie\Translatable\HasTranslations;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use HasTranslations, HasByname, IsResourceable, HasCustomCasts;

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        foreach ($this->getTranslatableAttributes() as $name) {
            $attributes[$name] = $this->getTranslation($name, app()->getLocale());
        }

        return $attributes;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        return $this->customCastAttribute($key, parent::castAttribute($key, $value));
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        return parent::setAttribute($key, $value)->customSetAttribute($key, $value);
    }

    /**
     * @return string
     */
    public function mainKey()
    {
        return $this->keyable ? $this->keyable[0] : $this->getKeyName();
    }
}
