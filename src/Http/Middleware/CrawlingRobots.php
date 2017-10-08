<?php

declare(strict_types=1);

namespace Cortex\Foundation\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;

class CrawlingRobots
{
    protected $response;

    public function handle(Request $request, Closure $next)
    {
        $this->response = $next($request);
        $shouldIndex = $this->shouldIndex($request);

        if (is_bool($shouldIndex)) {
            return $this->responseWithRobots($shouldIndex ? 'all' : 'none');
        }

        if (is_string($shouldIndex)) {
            return $this->responseWithRobots($shouldIndex);
        }

        throw new Exception('An indexing rule needs to return a boolean or a string.');
    }

    protected function responseWithRobots(string $contents)
    {
        $this->response->headers->set('x-robots-tag', $contents, false);

        return $this->response;
    }

    /**
     * @return string|bool
     */
    protected function shouldIndex(Request $request)
    {
        return app()->environment('production') && ! in_array($request->get('accessarea'), array_keys(config('cortex.foundation.route.prefix')));
    }
}
