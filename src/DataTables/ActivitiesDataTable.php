<?php

declare(strict_types=1);

namespace Cortex\Foundation\DataTables;

use Cortex\Foundation\Transformers\ActivityTransformer;

/**
 * @property \Spatie\Activitylog\Traits\CausesActivity $resource
 * @property string                                    $tabs
 * @property string                                    $id
 */
class ActivitiesDataTable extends LogsDataTable
{
    /**
     * {@inheritdoc}
     */
    protected $transformer = ActivityTransformer::class;

    /**
     * Get the query object to be processed by dataTables.
     *
     * @TODO: Apply row selection and bulk actions, check parent::query() for reference.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = $this->resource->actions();

        return $this->scope()->applyScopes($query);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            'id' => ['checkboxes' => json_decode('{"selectRow": true}'), 'exportable' => false, 'printable' => false],
            'details' => ['title' => '', 'data' => null, 'defaultContent' => '', 'class' => 'dt-details-control', 'searchable' => false, 'orderable' => false],
            'subject' => ['title' => trans('cortex/foundation::common.subject'), 'name' => 'subject.name', 'searchable' => false, 'orderable' => false, 'render' => 'full.subject_route ? "<a href=\""+full.subject_route+"\">"+data+"</a>" : data', 'responsivePriority' => 0],
            'description' => ['title' => trans('cortex/foundation::common.description'), 'orderable' => false],
            'created_at' => ['title' => trans('cortex/foundation::common.date'), 'render' => "moment(data).format('YYYY-MM-DD, hh:mm:ss A')"],
        ];
    }
}
