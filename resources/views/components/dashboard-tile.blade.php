<div class="grid-item col-md-{{ $width }}"
     id="{{ $name }}"
     style=" height: {{ $height }}px;"
     sortable="{{ $sortable ? 1 : 0 }}"
     resizable="{{ $resizable ? 1 : 0 }}"
     data-enable="{{ $is_enable ? 1 : 0 }}"
     data-title="{{ $title }}"
     data-color="{{ $color ?? 'default' }}"
     data-width="{{ $width }}"
     data-height="{{ $height }}"
     data-index="{{ $position }}">
    <div class="grid-item-content">
        <div class="panel panel-{{$color}}">
            <div class="panel-heading grid-card-handle">
                <span class="h4">{{ $title }}</span>
            </div>
            <div class="panel-body">
                @if(!empty($description))
                    <div class="margin-bottom">
                        <p>{{ $description }}</p>
                    </div>
                @endif
                <div class="dashboard-tile-content">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
