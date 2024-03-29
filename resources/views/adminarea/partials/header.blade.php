<header class="main-header fh-fixedHeader">
    <a href="#" class="logo" data-toggle="push-menu" role="button">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini"><i class="fa fa-home"></i></span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg"><b>{{ config('app.name') }}</b></span>
    </a>
    <nav class="navbar navbar-static-top">
        <div class="navbar-custom-menu pull-left">
            <ul class="nav navbar-nav">
                <li><a href="{{ route('adminarea.home') }}"><i class="fa fa-home"></i> {{ trans('cortex/foundation::common.home') }}</a></li>
            </ul>
        </div>

        <div class="navbar-custom-menu">
            {!! Menu::render('adminarea.header.language') !!}
            {!! Menu::render('adminarea.header.system') !!}
            {!! Menu::render('adminarea.header.user') !!}
        </div>
    </nav>
</header>
