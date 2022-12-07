<?php

declare(strict_types=1);

namespace Cortex\Foundation\DataTables;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Cortex\Foundation\Exceptions\GenericException;
use Cortex\Foundation\Transformers\DataArrayTransformer;
use Yajra\DataTables\Services\DataTable as BaseDataTable;

abstract class AbstractDataTable extends BaseDataTable
{
    /**
     * The model class.
     *
     * @var string
     */
    protected $model;

    /**
     * The transformer class.
     *
     * @var string
     */
    protected $transformer;

    /**
     * Available button actions.
     *
     * @var array
     */
    protected array $actions = ['print', 'csv', 'excel', 'pdf'];

    protected array $bulkActions = ['delete', 'revoke', 'activate', 'deactivate'];

    protected array $authorizedActions = ['create', 'import', 'export', 'print', 'delete', 'revoke', 'activate', 'deactivate'];

    /**
     * Set default options.
     *
     * @var array
     */
    protected $options;

    /**
     * Set action buttons.
     *
     * @var array
     */
    protected $buttons;

    /**
     * The datatable builder parameters.
     *
     * @var array
     */
    protected $builderParameters = [];

    /**
     * Get columns.
     *
     * @return array
     */
    abstract protected function getColumns(): array;

    /**
     * Create new instance of datatables.
     */
    public function __construct()
    {
        $this->buttons = $this->getAuthorizedButtons();
        $this->options = array_merge(config('cortex.foundation.datatables.options'), (array) $this->options);
    }

    /**
     * Get authorized buttons.
     *
     * @return array
     */
    public function getAuthorizedButtons(): array
    {
        $buttons = collect(config('cortex.foundation.datatables.buttons'))->merge($this->buttons)->mapWithKeys(function ($value, $key) {
            if (in_array($key, $this->authorizedActions)) {
                return [$key => ($user = $this->request()->user()) && $user->can($key === 'print' ? 'export' : $key, $this->model ? app($this->model) : []) && $value];
            }

            return [$key => $value];
        });

        return $buttons->toArray();
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $model = app($this->model);
        $query = $model->query();
        $selectedIds = collect($this->request()->input('selected_ids'))->filter();

        if ($selectedIds->isNotEmpty()) {
            $accessareas = (array) $model->obscure + app('accessareas')->where('is_obscured', true)->pluck('slug')->toArray();

            if (in_array($this->request()->accessarea(), $accessareas)) {
                $selectedIds = $selectedIds->map(function ($value) {
                    return optional(Hashids::decode($value))[0];
                });

                $query->whereIn($model->getKeyName(), $selectedIds);
            } else {
                $query->whereIn($model->getRouteKeyName(), $selectedIds);
            }
        }

        return $this->scope()->applyScopes($query);
    }

    /**
     * Add scopes to the datatable.
     *
     * @return $this
     */
    public function scope()
    {
        return $this;
    }

    /**
     * Check if the given action is enabled.
     *
     * @param $action
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return bool
     */
    public function isActionEnabled($action): bool
    {
        if (! Arr::get($this->buttons, $action)) {
            throw new GenericException(trans('cortex/foundation::messages.action_disabled'), $this->request->url());
        }

        return true;
    }

    /**
     * Check if the given action is authorized.
     *
     * @param $action
     * @param \Illuminate\Database\Eloquent\Model|null $item
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return bool
     */
    public function isActionAuthorized($action, Model $item = null): bool
    {
        if (in_array($action, $this->authorizedActions) && ! $this->request()->user()->can($action, $item ?? ($this->model ? app($this->model) : []))) {
            throw new GenericException(trans('cortex/foundation::messages.action_unauthorized'), $this->request->url());
        }

        return true;
    }

    /**
     * Perform bulk action.
     *
     * @param string $action
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkAction($action)
    {
        if ($results = $this->query()->get()) {
            $results->each(function ($item) use ($action) {
                if ($this->isActionAuthorized($action, $item)) {
                    $item->{$action}();
                }
            });

            return intend([
                'back' => true,
                'with' => ['success' => trans("cortex/foundation::messages.records_{$action}d")],
            ]);
        }

        return intend([
            'back' => true,
            'with' => ['warning' => trans('cortex/foundation::messages.no_records_selected')],
        ]);
    }

    /**
     * Display ajax response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajax(): JsonResponse
    {
        return datatables($this->query())
            ->setTransformer(app($this->transformer))
            ->make(true);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $data = <<<CDATA
function(data){
    var formData = $("{$this->getAjaxForm()}").find("input, select").serializeArray();
    $.each(formData, function(i, obj){
        data[obj.name] = obj.value;
    });
}
CDATA;

        return $this->builder()
                    ->columns($this->getColumns())
                    ->parameters($this->getBuilderParameters())
                    ->postAjax(['url' => $this->getAjaxUrl(), 'data' => $data]);
    }

    /**
     * Process DataTables needed render output.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function render($view, $data = [], $mergeData = [])
    {
        $action = $this->request()->get('action');

        // Export actions
        if (in_array($action, $this->actions)) {
            $this->isActionEnabled('export');
            $this->isActionAuthorized('export');

            return app()->call([$this, $action === 'print' ? 'printPreview' : $action]);
        }

        // Bulk actions
        if (in_array($action, $this->bulkActions)) {
            $this->isActionEnabled($action);
            $this->isActionAuthorized($action);

            return app()->call([$this, 'bulkAction'], ['action' => $action]);
        }

        if ($this->request()->ajax() && $this->request()->wantsJson()) {
            return app()->call([$this, 'ajax']);
        }

        return view($view, array_merge($this->attributes, $data), $mergeData)->with($this->dataTableVariable, $this->getHtmlBuilder());
    }

    /**
     * Get default builder parameters.
     *
     * @return array
     */
    protected function getBuilderParameters(): array
    {
        $buttons = $this->getButtons();

        return array_merge([
            'dom' => $this->options['dom'],
            'keys' => $this->options['keys'],
            'mark' => $this->options['mark'],
            'order' => $this->options['order'],
            'select' => $this->options['select'],
            'retrieve' => $this->options['retrieve'],
            'autoWidth' => $this->options['autoWidth'],
            'fixedHeader' => $this->options['fixedHeader'],
            'responsive' => $this->options['responsive'],
            'stateSave' => $this->options['stateSave'],
            'scrollX' => $this->options['scrollX'],
            'pageLength' => $this->options['pageLength'],
            'lengthMenu' => $this->options['lengthMenu'],
            'buttons' => $buttons,
            'initComplete' => $this->getAjaxForm() ? "function () {
                $('".$this->getAjaxForm()."').on('change',  (e)=> {
                    e.preventDefault();
                    this.api().draw();
                });
            }" : '',
        ], $this->builderParameters);
    }

    /**
     * Get buttons for datatable.
     *
     * @return array
     */
    protected function getButtons(): array
    {
        $this->buttons['bulk'] = collect($this->buttons)
            ->filter()->keys()->intersect($this->bulkActions)->isNotEmpty();
        $buttons = collect($this->buttons)->filter();
        $bulkButtons = $buttons->only($this->bulkActions);

        
        if ($customBulkActions = Arr::get($this->attributes, 'customBulkActions')) {
            foreach ($customBulkActions as $customBulkAction) {
                if (Arr::has($customBulkAction, ['title', 'url'])) {
                    $bulkButtons->put(Str::camel(Arr::get($customBulkAction, 'title')), true);
                }
            }
        }
        
        return collect([
            'create' => ['extend' => 'create', 'text' => '<i class="fa fa-plus"></i> '.trans('cortex/foundation::common.create')],
            'import' => ['extend' => 'import', 'text' => '<i class="fa fa-upload"></i> '.trans('cortex/foundation::common.import')],
            'create_popup' => ['extend' => 'create_popup', 'text' => '<i class="fa fa-plus"></i> '.trans('cortex/foundation::common.create')],

            'reset' => ['extend' => 'reset', 'text' => '<i class="fa fa-undo"></i> '.trans('cortex/foundation::common.reset')],
            'reload' => ['extend' => 'reload', 'text' => '<i class="fa fa-refresh"></i> '.trans('cortex/foundation::common.reload')],
            'showSelected' => ['extend' => 'showSelected', 'text' => '<i class="fa fa-check"></i> '.trans('cortex/foundation::common.showSelected')],

            'print' => ['extend' => 'print', 'text' => '<i class="fa fa-print"></i> '.trans('cortex/foundation::common.print')],
            'export' => ['extend' => 'export', 'text' => '<i class="fa fa-download"></i> '.trans('cortex/foundation::common.export').'&nbsp;<span class="caret"/>', 'autoClose' => true, 'fade' => 0],

            'bulk' => ['extend' => 'bulk', 'text' => '<i class="fa fa-list"></i> '.trans('cortex/foundation::common.bulk').'&nbsp;<span class="caret"/>', 'buttons' => $bulkButtons->keys(), 'autoClose' => true, 'fade' => 0],
            'colvis' => ['extend' => 'colvis', 'text' => '<i class="fa fa-columns"></i> '.trans('cortex/foundation::common.colvis').'&nbsp;<span class="caret"/>', 'fade' => 0],
            'pageLength' => ['extend' => 'pageLength', 'text' => '<i class="fa fa-list-ol"></i> '.trans('cortex/foundation::common.pageLength').'&nbsp;<span class="caret"/>', 'fade' => 0],
        ])->only($buttons->keys())->values()->toArray();
    }

    /**
     * Get Ajax URL.
     *
     * @return string
     */
    protected function getAjaxUrl(): string
    {
        return '';
    }

    /**
     * Get Ajax form.
     *
     * @return string
     */
    protected function getAjaxForm(): string
    {
        return '';
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        $model = $this->model ?? trim(str_replace('DataTable', '', mb_strrchr(static::class, '\\')), " \t\n\r\0\x0B\\");

        $resource = Str::plural(mb_strtolower(Arr::last(explode(class_exists($model) ? '\\' : '.', $model))));

        return $resource.'-export-'.date('Y-m-d').'-'.time();
    }

    /**
     * Map ajax response to columns definition.
     *
     * @param array|\Illuminate\Support\Collection $columns
     * @param string                               $type
     *
     * @return array
     */
    protected function mapResponseToColumns($columns, string $type): array
    {
        $transformer = new DataArrayTransformer();

        return array_map(function ($row) use ($columns, $type, $transformer) {
            return $transformer->transform($row, $columns, $type);
        }, $this->getAjaxResponseData());
    }
}
