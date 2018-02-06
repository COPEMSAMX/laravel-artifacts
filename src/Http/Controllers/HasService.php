<?php

namespace Gregoriohc\Artifacts\Http\Controllers;

use Gregoriohc\Artifacts\Artifacts;

trait HasService
{
    /**
     * @return \Gregoriohc\Artifacts\Services\ModelResourceService|\Gregoriohc\Artifacts\Services\ResourceService|\Gregoriohc\Artifacts\Services\Service
     */
    protected function service()
    {
        return Artifacts::service(static::bynameStudly());
    }
}
