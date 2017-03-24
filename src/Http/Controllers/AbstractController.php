<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Cortex Foundation Module.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Cortex Foundation Module
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

namespace Cortex\Foundation\Http\Controllers;

use Illuminate\Routing\Controller;
use Rinvex\Fort\Traits\GetsMiddleware;
use Illuminate\Foundation\Bus\DispatchesJobs;

abstract class AbstractController extends Controller
{
    use GetsMiddleware;
    use DispatchesJobs;

    /**
     * Whitelisted methods.
     * Array of whitelisted methods which do not need to go through middleware.
     *
     * @var array
     */
    protected $middlewareWhitelist = [];

    /**
     * The broker name.
     *
     * @var string
     */
    protected $broker;

    /**
     * Get the broker to be used.
     *
     * @return string
     */
    protected function getBroker()
    {
        return $this->broker;
    }
}