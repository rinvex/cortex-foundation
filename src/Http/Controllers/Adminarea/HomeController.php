<?php

declare(strict_types=1);

namespace Cortex\Foundation\Http\Controllers\Adminarea;

use Cortex\Foundation\Http\Controllers\AuthorizedController;
use Cortex\Foundation\Models\AdjustableLayout;
use Illuminate\Http\Request;

class HomeController extends AuthorizedController
{
    /**
     * {@inheritdoc}
     */
    protected $resource = 'access-adminarea';

    /**
     * Show the adminarea index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('cortex/foundation::adminarea.pages.index');
    }

    public function updateLayout(Request $request)
    {
        collect($request->get('items', []))->each( function ($item) {
            AdjustableLayout::query()->updateOrCreate([
                'element_id' => $item['element_id'],
                'created_by_id' => \Auth::id(),
                'created_by_type' => \Auth::user()->getMorphClass(),
            ], ['data' => $item['data']]);
        });
    }

    public function layoutMemory()
    {
        return response()->json([
            'items' => AdjustableLayout::query()->pluck('position', 'element_id')->toArray(),
        ]);
    }

}
