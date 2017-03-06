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

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuthorizedController extends AuthenticatedController
{
    use AuthorizesRequests;

    /**
     * Resource Ability Map.
     * Array of resource ability map.
     *
     * @var array
     */
    protected $resourceAbilityMap = [];

    /**
     * Resource action whitelist.
     * Array of resource actions to skip mapping to abilities automatically.
     *
     * @var array
     */
    protected $resourceActionWhitelist = [];

    /**
     * Create a new manage persistence controller instance.
     *
     * @throws \Rinvex\Fort\Exceptions\AuthorizationException
     */
    public function __construct()
    {
        parent::__construct();

        if (property_exists(static::class, 'resource')) {
            $this->authorizeResource($this->resource);
        } else {
            // At this stage, sessions still not loaded yet, and `AuthorizationException`
            // depends on seesions to flash redirection error msg, so delegate to a middleware
            // Since Laravel 5.3 controller constructors executed before middleware to be able to append
            // new middleware to the pipeline then all middleware executed together, and sessions started in `StartSession` middleware
            $this->middleware('can:null');
        }
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param string                        $resource
     * @param string|null                   $parameter
     * @param array                         $options
     * @param \Illuminate\Http\Request|null $request
     *
     * @return void
     */
    public function authorizeResource($resource, $parameter = null, array $options = [], $request = null)
    {
        $middleware = [];
        $parameter = $parameter ?: $resource;

        // Prepare middleware
        foreach ($this->mapResourceAbilities() as $method => $ability) {
            $middleware["can:{$ability}-{$resource},{$parameter}"][] = $method;
        }

        // Attach middleware
        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Map resource actions to resource abilities.
     *
     * @return array
     */
    protected function mapResourceAbilities()
    {
        // Reflect calling controller
        $controller = new ReflectionClass(static::class);

        // Get public methods and filter magic methods
        $methods = array_filter($controller->getMethods(ReflectionMethod::IS_PUBLIC), function ($item) use ($controller) {
            return $item->class === $controller->name && mb_substr($item->name, 0, 2) !== '__' && ! in_array($item->name, $this->resourceActionWhitelist);
        });

        // Get controller actions
        $actions = array_combine($items = array_map(function ($action) {
            return $action->name;
        }, $methods), $items);

        // Map resource actions to resourse abilities
        array_walk($actions, function ($value, $key) use (&$actions) {
            $actions[$key] = array_get($this->resourceAbilityMap(), $key, $value);
        });

        return $actions;
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return $this->resourceAbilityMap + [
                'index' => 'list',
                'store' => 'create',
                'edit' => 'update',
            ];
    }
}
