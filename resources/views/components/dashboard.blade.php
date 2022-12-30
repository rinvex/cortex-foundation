<div class="row">
    <div class="drag-container"></div>
    <div class="available-components hide col-md-2" style="padding-right: 0">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <span>{{ trans('cortex/foundation::common.available_components') }}</span>
            </div>
            <div class="panel-body">
                <div class="grid-2 columns drag-enabled" style="min-height: 200px">
                </div>
            </div>
        </div>
    </div>
    <div class="showing-components col-md-12">
        <div class="dashboard-options margin-bottom">
            <div class="row">
                <div class="col-md-2">
                    {{ Form::text('search', '', [ 'class' => 'grid-control-field form-control search-field', 'placeholder' => trans('cortex/foundation::common.search')]) }}
                </div>
                <div class="col-md-2">
                    {{ Form::select('sort', $dragOptions, 'drag', [ 'class' => 'grid-control-field form-control sort-field select2', 'placeholder' => trans ('cortex/foundation::common.sort') ]) }}
                </div>
                <div class="col-md-2">
                    {{ Form::select('filter', $colorOptions, 'all', [ 'class' => 'form-control grid-control-field filter-field select2', 'placeholder' => trans ('cortex/foundation::common.filter') ]) }}
                </div>
                <div class="col-md-2">
                    {{ Form::select('layout', $positionOptions, 'left-top', [ 'class' => 'form-control layout-field grid-control-field select2', 'id' => 'layout-field', 'placeholder' => trans ('cortex/foundation::common.layout') ]) }}
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary pull-right edit-dashboard-layout">{{ trans('cortex/foundation::common.layout_edit') }}</button>
                </div>
            </div>
        </div>
        <div class="dashboard-content" style="padding-right: 1rem">
            <div class="grid drag-enabled">
            </div>
        </div>
    </div>
    <div id="grid-items" style="display: none">
        {{ $slot }}
    </div>
</div>


@push('styles')
    <link href="{{ mix('css/muuri.css') }}" rel="stylesheet">
@endpush

@push('vendor-scripts')
    <script src="{{ mix('js/muuri.js') }}" defer></script>
    <script src="{{ mix('js/web-animations.min.js') }}" defer></script>
@endpush

@push('inline-scripts')
    <script src="{{ mix('js/adjustable-layout.js') }}" defer></script>
@endpush

