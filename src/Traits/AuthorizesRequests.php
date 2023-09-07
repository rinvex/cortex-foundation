<?php

declare(strict_types=1);

namespace Cortex\Foundation\Traits;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests as BaseAuthorizesRequests;

trait AuthorizesRequests
{
    use BaseAuthorizesRequests {
        resourceAbilityMap as protected parentResourceAbilityMap;
        resourceMethodsWithoutModels as protected parentResourceMethodsWithoutModels;
    }

    /**
     * Authorize a resource action (with model) based on the incoming request.
     *
     * @param string|array                  $model
     * @param string|array|null             $parameter
     * @param array                         $options
     * @param \Illuminate\Http\Request|null $request
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    public function authorizeResource($model, $parameter = null, array $options = [], $request = null): void
    {
        $middleware = [];

        $model = is_array($model) ? implode(',', $model) : $model;

        $parameter = is_array($parameter) ? implode(',', $parameter) : $parameter;

        $parameter = $parameter ?: app($model)->getMorphClass();

        foreach ($this->mapResourceAbilities() as $method => $ability) {
            $modelName = in_array($method, $this->resourceMethodsWithoutModels()) ? $model : $parameter;

            $middleware["can:{$ability},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Authorize a resource action (without model) based on the incoming request.
     *
     * @param string $resource
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    public function authorizeGeneric($resource): void
    {
        $middleware = [];

        foreach ($this->mapResourceAbilities() as $method => $ability) {
            $middleware["can:{$resource}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName)->only($methods);
        }
    }

    /**
     * Map resource actions to resource abilities.
     *
     * @throws \ReflectionException
     *
     * @return array
     */
    protected function mapResourceAbilities(): array
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
            $actions[$key] = Arr::get($this->resourceAbilityMap(), $key, $value);
        });

        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    protected function resourceAbilityMap(): array
    {
        return array_merge($this->parentResourceAbilityMap(), $this->resourceAbilityMap);
    }

    /**
     * {@inheritdoc}
     */
    protected function resourceMethodsWithoutModels()
    {
        return array_merge($this->parentResourceMethodsWithoutModels(), $this->resourceMethodsWithoutModels);
    }

    /**
     * Checks if the given string looks like a fully qualified class name.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function isClassName($value)
    {
        return mb_strpos($value, '\\') !== false;
    }
}
