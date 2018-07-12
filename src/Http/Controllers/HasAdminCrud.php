<?php

namespace Gregoriohc\Artifacts\Http\Controllers;

use Gregoriohc\Artifacts\Models\Model;
use Illuminate\Http\Request;
use Route;
use Illuminate\Support\Facades\View;

trait HasAdminCrud
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $config = $this->processedIndexConfig($request);

        return view($this->viewName(__FUNCTION__), [
            'modelSingular' => $this->modelSingular(),
            'modelPlural' => $this->modelPlural(),
            'title' => trans('models.' . $this->modelPlural() . '.plural'),
            'items' => $this->service()->findAll(10, [
                'filters' => $config['filters'],
                'search' => $config['search'],
                'order' => [
                    'direction' => 'desc',
                ],
            ]),
            'config' => $config,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request)
    {
        $id = end(Route::current()->parameters);

        $config = $this->processedShowConfig($request);

        return view($this->viewName(__FUNCTION__), [
            'modelSingular' => $this->modelSingular(),
            'modelPlural' => $this->modelPlural(),
            'title' => trans('models.' . $this->modelPlural() . '.plural'),
            'item' => $this->service()->findFirstById($id),
            'config' => $config,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $id = end(Route::current()->parameters);

        $config = $this->processedFormConfig($request) + [
                'action' => 'edit',
                'form_method' => 'PUT',
                'form_action_route' => str_replace('.edit', '.update', Route::currentRouteName()),
                'form_action_route_parameters' => Route::current()->parameters,
            ];

        return view($this->viewName(__FUNCTION__), [
            'modelSingular' => $this->modelSingular(),
            'modelPlural' => $this->modelPlural(),
            'title' => trans('models.' . $this->modelPlural() . '.plural'),
            'item' => $this->service()->findFirstById($id),
            'config' => $config,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $id = end(Route::current()->parameters);
        /** @var Model $item */
        $item = $this->service()->findFirstById($id);
        $config = $this->processedFormConfig($request);

        $data = $request->only(array_keys($config['columns']));
        foreach ($config['columns'] as $column => $options) {
            if ('flags' == $options['type'] && !isset($data[$column])) {
                $data[$column] = [];
            }
        }

        $item->update($data);

        $routeParameters = Route::current()->parameters;
        array_pop($routeParameters);

        return redirect()->route($this->routeName('index'), $routeParameters);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        $config = $this->processedFormConfig($request) + [
                'action' => 'create',
                'form_method' => 'POST',
                'form_action_route' => str_replace('.create', '.store', Route::currentRouteName()),
                'form_action_route_parameters' => Route::current()->parameters,
            ];

        return view($this->viewName(__FUNCTION__), [
            'modelSingular' => $this->modelSingular(),
            'modelPlural' => $this->modelPlural(),
            'title' => trans('models.' . $this->modelPlural() . '.plural'),
            'item' => null,
            'config' => $config,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $config = $this->processedFormConfig($request);

        $this->service()->create($request->only(array_keys($config['columns'])));

        return redirect()->route($this->routeName('index'), Route::current()->parameters);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Request $request)
    {
        $id = end(Route::current()->parameters);
        /** @var Model $item */
        $item = $this->service()->findFirstById($id);

        $item->delete();

        $routeParameters = Route::current()->parameters;
        array_pop($routeParameters);

        return redirect()->route($this->routeName('index'), $routeParameters);
    }

    /**
     * @param string $method
     * @return string
     */
    protected function viewName($method)
    {
        $method = snake_case($method);
        $view = 'admin.' . $this->modelPlural() . '.' . $method;
        if (!View::exists($view)) {
            $view = 'admin.resources.' . $method;
        }

        return $view;
    }

    /**
     * @param string $method
     * @return string
     */
    protected function routeName($method)
    {
        return 'admin.' . $this->modelPlural() . '.' . $method;
    }

    /**
     * @return string
     */
    protected function modelSingular()
    {
        return self::bynameSnake();
    }

    /**
     * @return string
     */
    protected function modelPlural()
    {
        return str_plural(self::bynameSnake());
    }

    /**
     * @param Request $request
     * @return array
     */
    abstract protected function indexConfig(Request $request);

    /**
     * @param Request $request
     * @return array
     */
    protected function showConfig(Request $request)
    {
        return $this->indexConfig($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function formConfig(Request $request)
    {
        return $this->showConfig($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processedIndexConfig(Request $request)
    {
        $config = $this->indexConfig($request);

        // Columns
        if (!isset($config['columns'])) {
            $config['columns'] = [
                'id',
            ];
        }
        $columns = $config['columns'];
        $processedColumns = [];
        foreach ($columns as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            if (is_array($value)) {
                $value = function ($key, $options, $item) {
                    return $this->processColumnValue($key, $options, $item);
                };
            }
            $processedColumns[$key] = $value;
        }
        $config['columns'] = $processedColumns;

        // Rows actions
        $config['row_actions'] = array_get($config, 'row_actions', []);
        $config['row_actions_crud'] = array_get($config, 'row_actions_crud', true);
        if ($config['row_actions_crud']) {
            $config['row_actions'] += [
                'show' => [
                    'classes' => 'btn-primary',
                ],
                'edit' => [
                    'classes' => 'btn-warning',
                ],
                'destroy' => [
                    'type' => 'button-modal',
                    'classes' => 'btn-danger',
                    'target' => '#resource-delete',
                ],
            ];
        }
        $rowActions = $config['row_actions'];
        $processedRowActions = [];
        foreach ($rowActions as $key => $value) {
            $value['type'] = array_get($value, 'type', 'link');
            $value['label'] = array_get($value, 'label', 'views.admin.components.model.index.action.' . $key);
            $value['classes'] = array_get($value, 'classes', 'btn-primary');
            $value['route'] = array_get($value, 'route', $this->routeName($key));
            $value['routeParameters'] = array_get($value, 'routeParameters', function() {
                return function($item, $key, $options) {
                    return Route::current()->parameters + [$item->id];
                };
            });
            if (! ($value['routeParameters'] instanceof \Closure)) {
                $value = $value['routeParameters'];
                $value['routeParameters'] = function($item, $key, $options) use ($value) {
                    return $value;
                };
            }
            $value['show'] = array_get($value, 'show', function() {
                return function($item, $key, $options) {
                    return true;
                };
            });
            $value['modalData'] = array_get($value, 'modalData', []);
            $processedRowActions[$key] = $value;
        }
        $config['row_actions'] = $processedRowActions;

        // Create route
        $config['create_route'] = array_get($config, 'create_route', $this->routeName('create'));
        $config['create_route_parameters'] = array_get($config, 'create_route_parameters', Route::current()->parameters);

        // Parent relation
        $config['parent_relation'] = array_get($config, 'parent_relation', []);
        $config['parent_relation']['type'] = array_get($config['parent_relation'], 'type', 'belongs_to');
        $config['parent_relation']['attribute'] = array_get($config['parent_relation'], 'attribute', null);
        $config['parent_relation']['model'] = array_get($config['parent_relation'], 'attribute', null);
        if ($config['parent_relation']['model'] && !$config['parent_relation']['attribute']) {
            $config['parent_relation']['attribute'] = snake_case(class_basename($config['parent_relation']['model']));
        } elseif ($config['parent_relation']['attribute'] && !$config['parent_relation']['model']) {
            $config['parent_relation']['model'] = 'App\\Models\\' . studly_case($config['parent_relation']['attribute']);
        }

        // Filters
        $config['filters'] = isset($config['filters']) ? $config['filters'] : [];

        // Query filters
        $requestFilters = $request->get('filter', []);
        foreach ($config['filters'] as $filter => $options) {
            if (isset($requestFilters[$filter])) {
                $config['filters'][$filter]['value'] = $requestFilters[$filter];
                $config['filters'][$filter]['operator'] = array_get($config['filters'][$filter], 'operator', '=');
            }
        }

        // Route path filter (parent relation)
        if (count(Route::current()->parameters)) {
            $parameters = Route::current()->parameters;
            $value = end($parameters);
            $key = key($parameters);
            if ('belongs_to' == $config['parent_relation']['type']) {
                $config['filters'][$key . '_id'] = [
                    'operator' => '=',
                    'value' => $value,
                ];
            } elseif ('morphs' == $config['parent_relation']['type']) {
                $config['filters'][$config['parent_relation']['attribute'] . '_id'] = [
                    'operator' => '=',
                    'value' => $value,
                ];
                $config['filters'][$config['parent_relation']['attribute'] . '_type'] = [
                    'operator' => '=',
                    'value' => 'App\\Models\\' . studly_case($key),
                ];
            }
        }

        // Search
        $config['search'] = array_get($config, 'search', []);
        $config['search']['attributes'] = array_get($config['search'], 'attributes', []);
        $config['search']['query'] = $request->get('q');
        $config['search']['route'] = Route::currentRouteName();
        $config['search']['routeParameters'] = Route::current()->parameters;
        $config['search']['filters'] = array_get($config['search'], 'filters', []);

        return $config;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processedShowConfig(Request $request)
    {
        $config = $this->showConfig($request);

        // Columns
        if (!isset($config['columns'])) {
            $config['columns'] = [
                'id',
            ];
        }
        $columns = $config['columns'];
        $processedColumns = [];
        foreach ($columns as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            if (is_array($value)) {
                $value = function ($key, $options, $item) {
                    return $this->processColumnValue($key, $options, $item);
                };
            }
            $processedColumns[$key] = $value;
        }
        $config['columns'] = $processedColumns;

        return $config;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processedFormConfig(Request $request)
    {
        $config = $this->formConfig($request);

        // Parent relation
        $config['parent_relation'] = array_get($config, 'parent_relation', []);
        $config['parent_relation']['type'] = array_get($config['parent_relation'], 'type', 'belongs_to');
        $config['parent_relation']['attribute'] = array_get($config['parent_relation'], 'attribute', null);
        $config['parent_relation']['model'] = array_get($config['parent_relation'], 'model', null);
        if ($config['parent_relation']['model'] && !$config['parent_relation']['attribute']) {
            $config['parent_relation']['attribute'] = snake_case(class_basename($config['parent_relation']['model']));
        } elseif ($config['parent_relation']['attribute'] && !$config['parent_relation']['model']) {
            $config['parent_relation']['model'] = 'App\\Models\\' . studly_case($config['parent_relation']['attribute']);
        }

        // Columns
        $config['columns'] = array_get($config, 'columns', []);
        $columns = $config['columns'];

        if (isset($config['parent_relation'])) {
            $idParent = end(Route::current()->parameters);
            $routerParametersKeys = array_keys(Route::current()->parameters);
            $attributeParent = end($routerParametersKeys);
            $modelParent = 'App\\Models\\' . studly_case($attributeParent);
            switch ($config['parent_relation']['type']) {
                case 'morphs': {
                    $columns[$config['parent_relation']['attribute'] . '_type'] = [
                        'type' => 'hidden',
                        'default' => $modelParent,
                    ];
                    $columns[$config['parent_relation']['attribute'] . '_id'] = [
                        'type' => 'hidden',
                        'default' => $idParent,
                    ];
                    break;
                }
                case 'belongs_to': {
                    $columns[$config['parent_relation']['attribute'] . '_id'] = [
                        'type' => 'hidden',
                        'default' => $idParent,
                    ];
                    break;
                }
            }
        }

        $processedColumns = [];
        foreach ($columns as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            if (!is_array($value)) $value = [];
            $value['type'] = array_get($value, 'type', 'text');
            $value['select_options'] = array_get($value, 'select_options', []);
            $value['select_multiple'] = array_get($value, 'select_multiple', false);
            $value['select_option_null'] = array_get($value, 'select_option_null', false);
            $value['default'] = array_get($value, 'default');
            $processedColumns[$key] = $value;
        }

        unset($processedColumns['id']);
        $config['columns'] = $processedColumns;

        return $config;
    }

    /**
     * @param Model $item
     * @param string $key
     * @param array $options
     * @return mixed
     */
    protected function processColumnValue($item, $key, $options)
    {
        return $item->$key;
    }
}
