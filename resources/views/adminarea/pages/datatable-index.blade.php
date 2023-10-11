{{-- Master Layout --}}
@extends('cortex/foundation::adminarea.layouts.default')

{{-- Page Title --}}
@section('title')
    {{ extract_title(Breadcrumbs::render()) }}
@endsection

{{-- Main Content --}}
@section('content')

    <div class="content-wrapper">
        <section class="content-header">
            <h1>{{ Breadcrumbs::render() }}</h1>
        </section>

        {{-- Main content --}}
        <section class="content">

            <div class="row">

                <div class="col-md-12">

                    <div class="box">
                        <div class="box-body">
                            @yield('datatable-filters')
                            {!! $dataTable->pusher($pusher ?? null)->routePrefix($routePrefix ?? null)->table(['id' => $id]) !!}
                        </div>
                    </div>

                </div>

            </div>

        </section>

    </div>

@endsection

@push('styles')
    <link href="{{ mix('css/datatables.css') }}" rel="stylesheet">
    <link href="{{ asset('css/searchPanes.css') }}" rel="stylesheet">
@endpush

@push('vendor-scripts')
    <script src="{{ mix('js/datatables.js') }}" defer></script>
@endpush

@push('inline-scripts')
    <script src="{{ asset('js/dataTables.searchPanes.js') }}" defer></script>
    <script src="{{ asset('js/searchPanes.bootstrap.js') }}" defer></script>
    {!! $dataTable->scripts() !!}
@endpush
