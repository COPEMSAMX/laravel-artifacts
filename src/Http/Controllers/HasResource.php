<?php

namespace Gregoriohc\Artifacts\Http\Controllers;

use Illuminate\Http\Request;

trait HasResource
{
    /**
     * @param \Gregoriohc\Artifacts\Support\Concerns\IsResourceable $item
     * @return mixed
     */
    protected function item($item)
    {
        return $this->service()->transformResourceItem($item);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @return mixed
     */
    protected function collection($collection)
    {
        return $this->service()->transformResourceCollection($collection);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $paginator = $this->service()->findAll();

        return $this->collection($paginator);
    }

    /**
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        /** @var \Gregoriohc\Artifacts\Support\Concerns\IsResourceable|null $resource */
        $resource = $this->service()->findBy($this->service()->resource()->mainKey(), $id)->first();

        if ($resource) {
            return $this->item($resource);
        }

        return response()->json(null, 400);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        /** @var \Gregoriohc\Artifacts\Support\Concerns\IsResourceable|null $resource */
        $resource = $this->service()->findBy($this->service()->resource()->mainKey(), $id)->first();

        $resource = $this->service()->update($resource, $request->all());

        return $this->item($resource);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        /** @var \Gregoriohc\Artifacts\Support\Concerns\IsResourceable $resource */
        $resource = $this->service()->create($request->all());

        return $this->item($resource);
    }
}
