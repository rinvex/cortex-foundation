<?php

declare(strict_types=1);

namespace Cortex\Foundation\Transformers;

use Illuminate\Support\Str;
use Cortex\Foundation\Models\Log;
use Rinvex\Support\Traits\Escaper;
use Illuminate\Support\Facades\Route;
use League\Fractal\TransformerAbstract;

class LogTransformer extends TransformerAbstract
{
    use Escaper;

    /**
     * Transform log model.
     *
     * @param \Cortex\Foundation\Models\Log $log
     *
     * @return array
     */
    public function transform(Log $log): array
    {
        $causer_route = '';

        if ($log->causer) {
            $class = explode('\\', get_class($log->causer));
            $singleResource = Str::lower(end($class));
            $pluralResource = Str::plural(Str::lower(end($class)));
            $causer = ucfirst($singleResource).': '.($log->causer->username ?? $log->causer->name ?? $log->causer->slug);
            // @TODO: identify the new route name. ex: adminarea.cortex.auth.members.edit
            $causer_route = Route::has("adminarea.{$pluralResource}.edit") ? route("adminarea.{$pluralResource}.edit", [$singleResource => $log->causer]) : null;
        } else {
            $causer = 'System';
        }

        return $this->escape([
            'id' => (int) $log->getKey(),
            'description' => (string) $log->description,
            'causer' => $causer,
            'causer_route' => $causer_route,
            'properties' => (object) $log->properties,
            'created_at' => (string) $log->created_at,
        ]);
    }
}
