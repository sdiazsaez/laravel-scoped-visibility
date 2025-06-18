<?php

namespace Cosmoscript\ScopedVisibility;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Middleware to apply scoped visibility filtering to Eloquent models
 * that use the HasScopedVisibility trait.
 *
 * This middleware registers a Builder macro called `applyVisibility`,
 * which looks for query parameters like:
 *
 *   ?visibility[imported]=only
 *   ?visibility[test_data]=with
 *
 * These flags automatically trigger `onlyScope()` or `withScope()` methods
 * on the Eloquent query builder for models that define a `scopedFlags()` method.
 *
 * This makes it possible to declaratively control data visibility via URL
 * parameters without modifying controller code.
 */
class ApplyScopedVisibility
{
    /**
     * Handle the incoming request and attach the `applyVisibility` macro to Builder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Builder::macro('applyVisibility', function (?array $flags = null) {
            if (is_null($flags)) {
                if (!RequestFacade::has('visibility')) {
                    return $this;
                }

                $flags = RequestFacade::input('visibility');
            }

            if (!is_array($flags) || empty($flags)) {
                return $this;
            }

            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = $this->getModel();

            if (!method_exists($model, 'scopedFlags')) {
                return $this;
            }

            $availableFlags = $model->scopedFlags();
            $validModes = ['only', 'with'];

            foreach ($flags as $key => $mode) {
                $mode = strtolower(trim($mode));

                if (!in_array($mode, $validModes, true)) {
                    continue;
                }

                if (!array_key_exists($key, $availableFlags)) {
                    continue;
                }

                $method = $mode . 'Scope';

                if (method_exists($this, $method)) {
                    $this->$method($key);
                }
            }

            return $this;
        });

        return $next($request);
    }
}
