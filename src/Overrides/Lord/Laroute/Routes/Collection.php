<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Lord\Laroute\Routes;

use Illuminate\Routing\Route;
use Illuminate\Routing\AbstractRouteCollection;
use Lord\Laroute\Routes\Collection as BaseCollection;
use Lord\Laroute\Routes\Exceptions\ZeroRoutesException;

class Collection extends BaseCollection
{
    public function __construct(AbstractRouteCollection $routes, $filter, $namespace)
    {
        $this->items = $this->parseRoutes($routes, $filter, $namespace);
    }

    /**
     * Parse the routes into a jsonable output.
     *
     * @param AbstractRouteCollection $routes
     * @param string                  $filter
     * @param string                  $namespace
     *
     * @throws ZeroRoutesException
     *
     * @return array
     */
    protected function parseRoutes(AbstractRouteCollection $routes, $filter, $namespace)
    {
        $this->guardAgainstZeroRoutes($routes);

        $results = [];

        foreach ($routes as $route) {
            $results[] = $this->getRouteInformation($route, $filter, $namespace);
        }

        return array_values(array_filter($results));
    }

    /**
     * Throw an exception if there aren't any routes to process.
     *
     * @param AbstractRouteCollection $routes
     *
     * @throws ZeroRoutesException
     */
    protected function guardAgainstZeroRoutes(AbstractRouteCollection $routes)
    {
        if (count($routes) < 1) {
            throw new ZeroRoutesException("You don't have any routes!");
        }
    }

    /**
     * Get the route information for a given route.
     *
     * @param $route \Illuminate\Routing\Route
     * @param $filter string
     * @param $namespace string
     *
     * @return array
     */
    protected function getRouteInformation(Route $route, $filter, $namespace): ?array
    {
        $uri = $route->uri();
        $host = $route->domain();
        $name = $route->getName();
        $laroute = $route->getAction('laroute');

        switch ($filter) {
            case 'all':
                if ($laroute === false) {
                    return null;
                }
                break;
            case 'only':
                if ($laroute !== true) {
                    return null;
                }
                break;
        }

        return compact('host', 'uri', 'name');
    }
}
