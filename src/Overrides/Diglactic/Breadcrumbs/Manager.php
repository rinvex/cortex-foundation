<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Diglactic\Breadcrumbs;

use Illuminate\Contracts\View\View;
use Diglactic\Breadcrumbs\Manager as BaseManager;
use Diglactic\Breadcrumbs\Exceptions\ViewNotSetException;
use Diglactic\Breadcrumbs\Exceptions\UnnamedRouteException;

class Manager extends BaseManager
{
    /**
     * Register a breadcrumb-generating callback for a page.
     *
     * @param string   $name     The name of the page.
     * @param callable $callback The callback, which should accept a Generator instance as the first parameter and may
     *                           accept additional parameters.
     *
     * @throws \Diglactic\Breadcrumbs\Exceptions\DuplicateBreadcrumbException If the given name has already been used.
     *
     * @return void
     */
    public function for(string $name, callable $callback): void
    {
        //if (isset($this->callbacks[$name])) {
        //    throw new DuplicateBreadcrumbException($name);
        //}

        $this->callbacks[$name] = $callback;
    }

    /**
     * Render breadcrumbs for a page with the default view.
     *
     * @param string|null $name      The name of the current page.
     * @param mixed       ...$params The parameters to pass to the closure for the current page.
     *
     * @throws \Diglactic\Breadcrumbs\Exceptions\InvalidBreadcrumbException if the name is (or any ancestor names are)
     *                                                                      not registered.
     * @throws \Diglactic\Breadcrumbs\Exceptions\UnnamedRouteException      if no name is given and the current route doesn't
     *                                                                      have an associated name.
     * @throws \Diglactic\Breadcrumbs\Exceptions\ViewNotSetException        if no view has been set.
     *
     * @return \Illuminate\Contracts\View\View The generated view.
     */
    public function render(?string $name = null, ...$params): View
    {
        $accessarea = request()->accessarea();

        if (! view()->exists($view = "cortex/foundation::{$accessarea}.partials.breadcrumbs")) {
            throw new ViewNotSetException('Breadcrumbs view not found!');
        }

        return $this->view($view, $name, ...$params);
    }

    /**
     * Get the current route name and parameters.
     *
     * This may be the route set manually with the setCurrentRoute() method, but normally is the route retrieved from
     * the Laravel Router.
     *
     * #### Example
     * ```php
     * [$name, $params] = $this->getCurrentRoute();
     * ```
     *
     * @throws \Diglactic\Breadcrumbs\Exceptions\UnnamedRouteException if the current route doesn't have an associated name.
     *
     * @return array A two-element array consisting of the route name (string) and any parameters (array).
     */
    protected function getCurrentRoute(): array
    {
        // Manually set route
        if ($this->route) {
            return $this->route;
        }

        // Determine the current route
        $route = $this->router->current();

        // No current route - must be the 404 page
        if ($route === null) {
            return ['errors.404', []];
        }

        // Convert route to name
        $name = $route->getName();

        if ($name === null) {
            throw new UnnamedRouteException($route);
        }

        // Get the current route parameters
        // Don't use the default logic, bug introduced!
        // https://github.com/diglactic/laravel-breadcrumbs/issues/63
        $params = array_values($route->parameters());

        return [$name, $params];
    }
}
