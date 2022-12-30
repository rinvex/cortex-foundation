<?php

namespace Cortex\Foundation\View\Components;

use Cortex\Foundation\Models\AdjustableLayout;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

class DashboardTile extends Component
{
    public string $name;

    public string $title;

    public null|string $color;

    public null|bool $resizable;

    public null|bool $sortable;

    public null|bool $is_enable;

    public int $position = 1000;

    public int $width = 3;

    public int $height = 300;

    public array $colors = [
        'gray'   => 'default',
        'blue'   => 'primary',
        'green'   => 'success',
        'red'    => 'danger',
    ];
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $id,
        string $title,
        ?string $color = 'gray',
        ?bool $sortable = true,
        ?bool $resizable = false,
        ?bool $is_enable = true
    )
    {
        $this->name = $id;
        $this->title = $title;
        $this->resizable = $resizable;
        $this->sortable = $sortable;
        $this->is_enable = $is_enable;

        $this->color = Arr::get($this->colors, $color, 'default');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $this->adjustSettings();
        return view('cortex/foundation::components.dashboard-tile');
    }

    private function adjustSettings()
    {
        $setting = AdjustableLayout::query()->createdBy(\Auth::user())->where('element_id', $this->name)->first();
        if ($setting) {
            $this->position = Arr::get($setting->data, 'position', $this->position);
            $this->width = intval(Arr::get($setting->data, 'width', $this->width));
            $this->height = intval(Arr::get($setting->data, 'height', $this->height));
            $this->is_enable = Arr::get($setting->data, 'is_enable', $this->is_enable);
        }
    }

}
