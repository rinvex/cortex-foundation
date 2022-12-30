<?php

namespace Cortex\Foundation\View\Components;

use Illuminate\View\Component;

class Dashboard extends Component
{
    public array $dragOptions = [
        'drag'  => 'drag',
        'title' => 'title',
    ];

    public array $colorOptions = [
        'all'       => 'All',
        'default'   => 'Gray',
        'primary'   => 'Blue',
        'success'   => 'Green',
        'danger'    => 'Red',
    ];

    public array $positionOptions = [
        'left-top'          => 'Left Top',
        'right-top'         => 'Right Top',
        'left-bottom'       => 'Left Bottom',
        'right-bottom'      => 'Right Bottom',
    ];
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('cortex/foundation::components.dashboard');
    }
}
