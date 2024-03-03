<?php

declare(strict_types=1);

namespace Cortex\Foundation\DataTables;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Cortex\Foundation\Exceptions\GenericException;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    protected array $authorizableActions = ['create', 'import', 'export', 'print', 'delete', 'revoke', 'activate', 'deactivate'];

    /**
     * Set default options.
     *
     * @var array
     */
    protected $options;

    /**
     * Action buttons.
     *
     * @var array
     */
    protected $buttons;

    /**
     * Buttons that are both authorized and enabled.
     *
     * @var array
     */
    protected $authorizedButtons;

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
        parent::__construct();

        $this->authorizedButtons = $this->getAuthorizedButtons();
        $this->options = array_merge(config('cortex.foundation.datatables.options'), (array) $this->options);
        $this->options['language'] = [
            'search' => trans('cortex/foundation::common.datatable_language.search'),
            'searchPlaceholder' => trans('cortex/foundation::common.datatable_language.search_placeholder'),
            'paginate' => ['previous' => trans('cortex/foundation::common.datatable_language.previous'), 'next' => trans('cortex/foundation::common.datatable_language.next')],
        ];
    }

    /**
     * Get only buttons that are both authorized and enabled.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAuthorizedButtons(): Collection
    {
        $buttons = collect(config('cortex.foundation.datatables.buttons'))->merge($this->buttons)->mapWithKeys(function ($value, $key) {
            if (in_array($key, $this->authorizableActions)) {
                return [$key => $this->request()?->user()?->can($key === 'print' ? 'export' : $key, $this->model ? app($this->model) : [])];
            }

            return [$key => $value];
        });

        return $buttons->filter();
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $model = app($this->model);
        $currentUser = $this->request()->user();
        $morphMap = array_flip(Relation::morphMap());
        $query = $model->query();

        // 1. User is not a superadmin
        if (! $currentUser->isA('superadmin')) {
            // 2. User can not list entities
            if (! $currentUser->can('list', $model)) {
                $query->whereNull($model->getKeyName());
            }

            // 3. User can view only owned entities
            $currentUser->getAbilities()->whereNotNull('entity_type')->where(function ($ability) use ($morphMap) {
                return $ability->entity_type === $morphMap[$this->model] && $ability->name === 'view' && $ability->only_owned;
            })->whenNotEmpty(fn () => $query->where('created_by_id', $currentUser->getAuthIdentifier())->where('created_by_type', $currentUser->getMorphClass()));

            // 4. User can view specific entities
            $currentUser->getAbilities()->whereNotNull('entity_type')->whereNotNull('entity_id')->where(function ($ability) use ($morphMap) {
                return $ability->entity_type === $morphMap[$this->model] && $ability->name === 'view' && ! $ability->only_owned;
            })->pluck('entity_id')->whenNotEmpty(fn ($entities) => $query->OrWhereIn($model->getKeyName(), $entities->toArray()));
        }

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
     * Check if the given action is authorized.
     *
     * @param                                          $action
     * @param \Illuminate\Database\Eloquent\Model|null $item
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return bool
     */
    public function isActionAuthorized($action, Model $item = null): bool
    {
        if (! $this->authorizedButtons->has($action) || ! $this->request()->user()->can($action, $item ?? ($this->model ? app($this->model) : []))) {
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
            ->whitelist(array_keys($this->getColumns()))
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
     * @phpstan-param view-string|null $view
     *
     * @param string|null $view
     * @param array       $data
     * @param array       $mergeData
     *
     * @throws \Cortex\Foundation\Exceptions\GenericException
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function render(string $view = null, array $data = [], array $mergeData = [])
    {
        $action = $this->request()->get('action');

        // Export actions
        if (in_array($action, $this->actions) && method_exists($this, $action) && $this->isActionAuthorized('export')) {
            return app()->call([$this, $action === 'print' ? 'printPreview' : $action]);
        }

        // Bulk actions
        if (in_array($action, $this->bulkActions) && $this->isActionAuthorized($action)) {
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
            'language' => $this->options['language'],
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
        $bulkButtons = $this->authorizedButtons->only($this->bulkActions);
        $this->authorizedButtons['bulk'] = $bulkButtons->isNotEmpty();

        return collect([
            'create' => ['extend' => 'create', 'text' => '<i class="fa fa-plus"></i> '.trans('cortex/foundation::common.create')],
            'import' => ['extend' => 'import', 'text' => '<i class="fa fa-upload"></i> '.trans('cortex/foundation::common.import')],
            'create_popup' => ['extend' => 'create_popup', 'text' => '<i class="fa fa-plus"></i> '.trans('cortex/foundation::common.create')],

            'reset' => ['extend' => 'reset', 'text' => '<i class="fa fa-undo"></i> '.trans('cortex/foundation::common.reset')],
            'reload' => ['extend' => 'reload', 'text' => '<i class="fa fa-refresh"></i> '.trans('cortex/foundation::common.reload')],
            'showSelected' => ['extend' => 'showSelected', 'text' => '<i class="fa fa-check"></i> '.trans('cortex/foundation::common.showSelected')],

            'print' => ['extend' => 'print', 'text' => '<i class="fa fa-print"></i> '.trans('cortex/foundation::common.print')],
            'export' => ['extend' => 'export', 'text' => '<i class="fa fa-download"></i> '.trans('cortex/foundation::common.export'), 'autoClose' => true, 'fade' => 0],

            'bulk' => ['extend' => 'bulk', 'text' => '<i class="fa fa-list"></i> '.trans('cortex/foundation::common.bulk'), 'buttons' => $bulkButtons->keys(), 'autoClose' => true, 'fade' => 0],
            'colvis' => ['extend' => 'colvis', 'text' => '<i class="fa fa-columns"></i> '.trans('cortex/foundation::common.colvis'), 'fade' => 0],
            'pageLength' => ['extend' => 'pageLength', 'text' => '<i class="fa fa-list-ol"></i> '.trans('cortex/foundation::common.pageLength'), 'fade' => 0],
        ])->only($this->authorizedButtons->keys())->values()->toArray();
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
