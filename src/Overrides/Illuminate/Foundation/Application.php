<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Illuminate\Foundation;

use Illuminate\Foundation\Mix;
use Cortex\Foundation\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Cortex\Foundation\Providers\RoutingServiceProvider;
use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Foundation\PackageManifest as BasePackageManifest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Application extends BaseApplication
{
    /**
     * Register all base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        $this->register(new RoutingServiceProvider($this));
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);
        $this->singleton(Mix::class);

        $this->singleton(BasePackageManifest::class, function () {
            return new PackageManifest(
                new Filesystem(),
                $this->basePath(),
                $this->getCachedPackagesPath()
            );
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(SymfonyRequest $request, int $type = self::MAIN_REQUEST, bool $catch = true): SymfonyResponse
    {
        return $this[HttpKernelContract::class]->handle(Request::createFromBase($request));
    }

    /**
     * Get the application namespace.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get the path to the application "modules" directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function modulePath($path = '')
    {
        return $this->joinPaths(config('rinvex.composer.cortex-module.path'), $path);
    }

    /**
     * Get the path to the application "extensions" directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function extensionPath($path = '')
    {
        return $this->joinPaths(config('rinvex.composer.cortex-extension.path'), $path);
    }
}
