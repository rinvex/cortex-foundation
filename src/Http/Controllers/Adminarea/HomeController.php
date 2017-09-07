<?php

declare(strict_types=1);

namespace Cortex\Foundation\Http\Controllers\Adminarea;

use Cortex\Foundation\Http\Controllers\AuthorizedController;

class HomeController extends AuthorizedController
{
    /**
     * {@inheritdoc}
     */
    protected $resource = 'dashboard';

    /**
     * {@inheritdoc}
     */
    protected $resourceAbilityMap = ['index' => 'access'];

    /**
     * Show the dashboard home.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cortex/foundation::adminarea.pages.home');
    }
}